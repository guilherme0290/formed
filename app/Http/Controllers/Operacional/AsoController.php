<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Operacional\Concerns\ValidatesClientePortalTaskEditing;
use App\Http\Controllers\Controller;
use App\Models\Anexos;
use App\Models\AsoSolicitacoes;
use App\Models\Cliente;
use App\Models\ClienteGhe;
use App\Models\ClienteUnidadePermitida;
use App\Models\ClienteTabelaPreco;
use App\Models\ClienteTabelaPrecoItem;
use App\Models\Funcao;
use App\Models\Funcionario;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\UnidadeClinica;
use App\Services\AsoGheService;
use App\Services\ContratoClienteService;
use App\Services\TempoTarefaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AsoController extends Controller
{
    use ValidatesClientePortalTaskEditing;

    public function asoStore(Cliente $cliente, Request $request)
    {
        $usuario = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $this->normalizeTreinamentosInput($request);

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente->id);
        $treinamentosPermitidos = $this->getTreinamentosPermitidosAvulsos($empresaId, $cliente->id, $treinamentosDisponiveis);
        $codigosPacotes = $this->getTreinamentosCodigosPacotes($pacotesTreinamentos, $treinamentosDisponiveis);
        $treinamentosCodigos = array_values(array_unique(array_merge($treinamentosPermitidos, $codigosPacotes)));
        $temTreinamentosPermitidos = !empty($treinamentosPermitidos) || !empty($pacotesTreinamentos);
        $pacotesIds = collect($pacotesTreinamentos)->pluck('contrato_item_id')->filter()->values()->all();
        $unidadesPermitidasIds = $this->unidadesPermitidasIds($empresaId, $cliente->id);

        $data = $request->validate([
            'funcionario_id' => ['nullable', 'exists:funcionarios,id'],
            'nome' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'rg' => ['nullable', 'string', 'max:255'],
            'celular' => ['nullable', 'string', 'max:30'],
            'funcao_id' => ['nullable', 'integer', 'exists:funcoes,id'],
            'tipo_aso' => ['required', 'in:admissional,periodico,demissional,mudanca_funcao,retorno_trabalho'],
            'data_aso' => ['required', 'date_format:Y-m-d'],
            'unidade_id' => ['required', 'integer', Rule::in($unidadesPermitidasIds)],
            'vai_fazer_treinamento' => ['nullable', 'boolean'],
            'treinamento_modo' => ['nullable', Rule::in(['avulsos', 'pacotes'])],
            'pacote_id' => ['nullable', 'integer', Rule::in($pacotesIds)],
            'treinamentos' => ['array'],
            'treinamentos.*' => ['string', Rule::in($treinamentosCodigos)],
            'email_aso' => ['nullable', 'email'],
            'pcmso_elaborado_formed' => ['required', 'boolean'],
            'anexos' => ['nullable', 'array'],
            'anexos.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:10240'],
        ], $this->mensagensValidacao(), $this->atributosValidacao());

        $this->validarUploadPcmsoExterno($request, $data);

        $tiposAsoPermitidos = $this->tiposAsoPermitidos($cliente->id, $empresaId);
        if (empty($tiposAsoPermitidos)) {
            throw ValidationException::withMessages([
                'tipo_aso' => 'ASO não disponível para este cliente. Fale com seu comercial.',
            ]);
        }
        if (!in_array($data['tipo_aso'], $tiposAsoPermitidos, true)) {
            throw ValidationException::withMessages([
                'tipo_aso' => 'ASO não disponível para este tipo. Fale com seu comercial.',
            ]);
        }

        if (!empty($data['vai_fazer_treinamento']) && !$temTreinamentosPermitidos) {
            throw ValidationException::withMessages([
                'treinamentos' => 'Serviço não contratado converse com seu comercial',
            ]);
        }

        $pacoteSelecionado = $this->resolvePacoteTreinamento($data, $pacotesTreinamentos);

        $tarefa = DB::transaction(function () use ($data, $empresaId, $cliente, $usuario, $request, $pacoteSelecionado) {

            // 1) Resolve funcionário (existente ou novo)
            if (!empty($data['funcionario_id'])) {
                $funcionario = Funcionario::where('empresa_id', $empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->with('funcao')
                    ->findOrFail($data['funcionario_id']);
                if (!empty($data['celular'])) {
                    $funcionario->celular = $data['celular'];
                    $funcionario->save();
                }
            } else {
                $funcionario = Funcionario::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'nome' => $data['nome'],
                    'cpf' => $data['cpf'] ?? null,
                    'rg' => $data['rg'],
                    'celular' => $data['celular'] ?? null,
                    'data_nascimento' => $data['data_nascimento'],
                    'funcao_id' => $data['funcao_id'] ?? null,
                ]);
            }

            // 2) Coluna inicial do Kanban
            $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
                ->where('slug', 'pendente')
                ->first()
                ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

            $servicoAsoId = app(AsoGheService::class)->resolveServicoAsoId($cliente->id, $empresaId);
            if (!$servicoAsoId) {
                throw ValidationException::withMessages([
                    'contrato' => 'Não é possível criar a solicitação de ASO porque o contrato ativo não possui serviço vinculado ao GHE.',
                ]);
            }

            // 3) Monta título e descrição "humana" (mas agora só pra exibir)
            $tipoAsoLabel = match ($data['tipo_aso']) {
                'admissional' => 'Admissional',
                'periodico' => 'Periódico',
                'demissional' => 'Demissional',
                'mudanca_funcao' => 'Mudança de Função',
                'retorno_trabalho' => 'Retorno ao Trabalho',
            };

            $titulo = "ASO - {$funcionario->nome}";
            $descricao = "Tipo: {$tipoAsoLabel}";

            if (
                $data['tipo_aso'] === 'mudanca_funcao'
                && !empty($data['funcionario_id'])
                && !empty($data['funcao_id'])
            ) {
                $funcaoAnteriorNome = optional($funcionario->funcao)->nome ?? 'Não informada';
                $funcionario->funcao_id = $data['funcao_id'];
                $funcionario->save();

                $funcaoNovaNome = Funcao::find($data['funcao_id'])->nome ?? 'Não informada';
                $descricao .= " | Mudança de função: {$funcaoAnteriorNome} → {$funcaoNovaNome}";
            }

            $funcaoIdCheck = $data['funcao_id'] ?? $funcionario->funcao_id;
            $this->assertGheParaFuncao($empresaId, $cliente->id, $funcaoIdCheck);
            $this->assertGheParaFuncaoTipo($empresaId, $cliente->id, $funcaoIdCheck, $data['tipo_aso']);

            if (!empty($data['vai_fazer_treinamento']) && !empty($data['treinamentos'])) {
                $labels = [];
                foreach ($data['treinamentos'] as $codigo) {
                    $labels[] = $treinamentosDisponiveis[$codigo] ?? $codigo;
                }
                $descricao .= ' | Treinamentos: ' . implode(', ', $labels);
            }
            if (!empty($data['vai_fazer_treinamento']) && !empty($pacoteSelecionado['nome'])) {
                $descricao .= ' | Pacote: ' . $pacoteSelecionado['nome'];
            }

            $inicioPrevisto = Carbon::createFromFormat('Y-m-d H:i:s', $data['data_aso'] . ' 07:00:00');
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, $servicoAsoId);

            // 4) Cria a tarefa
            $tarefa = Tarefa::create([
                'empresa_id' => $empresaId,
                'coluna_id' => optional($colunaInicial)->id,
                'cliente_id' => $cliente->id,
                'responsavel_id' => $usuario->id,
                'funcionario_id' => $funcionario->id,
                'servico_id' => $servicoAsoId,
                'titulo' => $titulo,
                'descricao' => $descricao,
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto' => $fimPrevisto,
            ]);

            $anexosNovos = Anexos::salvarDoRequest($request, 'anexos', [
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'tarefa_id'      => $tarefa->id,
                'funcionario_id' => $funcionario->id ?? null,
                'uploaded_by'    => $usuario->id,
                'servico'        => 'ASO',
                'subpath'        => 'anexos-custom/' . $empresaId,
            ]);

            $pcmsoElaboradoFormed = $this->isPcmsoElaboradoPelaFormed($data);
            $pcmsoExternoAnexoId = $pcmsoElaboradoFormed ? null : optional($anexosNovos->first())->id;

            // 5) Cria o registro específico de ASO
            AsoSolicitacoes::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
                'funcionario_id' => $funcionario->id,
                'unidade_id' => $data['unidade_id'],
                'tipo_aso' => $data['tipo_aso'],
                'data_aso' => $data['data_aso'],
                'email_aso' => $data['email_aso'] ?? null,
                'pcmso_elaborado_formed' => $pcmsoElaboradoFormed,
                'pcmso_externo_anexo_id' => $pcmsoElaboradoFormed ? null : $pcmsoExternoAnexoId,
                'vai_fazer_treinamento' => !empty($data['vai_fazer_treinamento']),
                'treinamentos' => $data['treinamentos'] ?? [],
                'treinamento_pacote' => !empty($data['vai_fazer_treinamento']) ? $pacoteSelecionado : null,
            ]);

            // 6) Log inicial
            TarefaLog::create([
                'tarefa_id' => $tarefa->id,
                'user_id' => $usuario->id,
                'de_coluna_id' => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao' => 'criado',
                'observacao' => $descricao,
            ]);

            return $tarefa;
        });

        $origem = $request->query('origem');

        if ($origem === 'cliente') {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', "Agendamento ASO criado com sucesso para {$tarefa->titulo}.");
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', "Tarefa ASO agendada {$tarefa->titulo}.");
    }

    public function edit(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $empresaId = Auth::user()->empresa_id;
        $cliente = $tarefa->cliente;

        $anexos = $tarefa->anexos()
            ->orderByDesc('created_at')
            ->get();

        $tiposAso = [
            'admissional' => 'Admissional',
            'periodico' => 'Periódico',
            'demissional' => 'Demissional',
            'mudanca_funcao' => 'Mudança de Função',
            'retorno_trabalho' => 'Retorno ao Trabalho',
        ];

        $funcionarios = Funcionario::where('cliente_id', $cliente->id)
            ->orderBy('nome')
            ->get();

        $contratoAtivo = app(ContratoClienteService::class)
            ->getContratoAtivo($cliente->id, $empresaId, null);
        if ($contratoAtivo && !$contratoAtivo->relationLoaded('itens')) {
            $contratoAtivo->load('itens');
        }

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaContrato($contratoAtivo, $empresaId);
        if ($funcoes->isEmpty()) {
            $funcoes = app(AsoGheService::class)
                ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);
        }

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId);

        $aso = $tarefa->asoSolicitacao; // pode ser null para tarefas antigas

        // DATA
        $dataAso = old('data_aso');

        if (!$dataAso) {
            if ($aso && $aso->data_aso) {
                $dataAso = $aso->data_aso->format('Y-m-d');
            } elseif ($tarefa->inicio_previsto) {
                $dataAso = Carbon::parse($tarefa->inicio_previsto)->format('Y-m-d');
            }
        }

        // UNIDADE
        $unidadeSelecionada = old('unidade_id');

        if (!$unidadeSelecionada) {
            if ($aso) {
                $unidadeSelecionada = $aso->unidade_id;
            } elseif ($tarefa->descricao && preg_match('/Unidade ID:\s*(\d+)/', $tarefa->descricao, $m)) {
                $unidadeSelecionada = $m[1];
            }
        }

        // TREINAMENTOS
        $treinamentosSelecionados = old('treinamentos', []);

        if (empty($treinamentosSelecionados)) {
            if ($aso && !empty($aso->treinamentos)) {
                $treinamentosSelecionados = $aso->treinamentos;
            } elseif ($tarefa->descricao && preg_match('/Treinamentos:\s*(.+)$/i', $tarefa->descricao, $m)) {
                $lista = explode(',', $m[1]);
                $treinamentosSelecionados = array_map('trim', $lista);
            }
        }

        $treinamentosSelecionados = $this->normalizeTreinamentosValues($treinamentosSelecionados);
        $treinamentosPermitidos = $this->getTreinamentosPermitidosAvulsos($empresaId, $cliente->id, $treinamentosDisponiveis);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente->id);
        if (!$aso || empty($aso->treinamento_pacote)) {
            $treinamentosPermitidos = array_values(array_unique(array_merge(
                $treinamentosPermitidos,
                $treinamentosSelecionados
            )));
        }

        $unidades = $this->unidadesParaAgendamento(
            $empresaId,
            $cliente->id,
            $unidadeSelecionada ? (int) $unidadeSelecionada : null
        );

        // VAI FAZER TREINAMENTO
        $vaiFazerTreinamento = (int)old(
            'vai_fazer_treinamento',
            $aso ? (int)$aso->vai_fazer_treinamento : 0
        );

        if ($vaiFazerTreinamento === 0 && !empty($treinamentosSelecionados)) {
            $vaiFazerTreinamento = 1;
        }

        $tiposAsoPermitidos = $this->tiposAsoPermitidos($cliente->id, $empresaId);
        $tipoAsoAtual = $aso?->tipo_aso;
        if ($tipoAsoAtual && !in_array($tipoAsoAtual, $tiposAsoPermitidos, true)) {
            $tiposAsoPermitidos[] = $tipoAsoAtual;
        }

        return view('operacional.kanban.aso.create', [
            'cliente' => $cliente,
            'tarefa' => $tarefa,
            'tiposAso' => $tiposAso,
            'tiposAsoPermitidos' => $tiposAsoPermitidos,
            'funcionarios' => $funcionarios,
            'funcoes' => $funcoes,
            'unidades' => $unidades,
            'dataAso' => $dataAso,
            'anexos' => $anexos,
            'unidadeSelecionada' => $unidadeSelecionada,
            'vaiFazerTreinamento' => $vaiFazerTreinamento,
            'treinamentosDisponiveis' => $treinamentosDisponiveis,
            'treinamentosPermitidos' => $treinamentosPermitidos,
            'treinamentosSelecionados' => $treinamentosSelecionados,
            'pacotesTreinamentos' => $pacotesTreinamentos,
            'isEdit' => true,
            'asoResumoUrl' => route('operacional.kanban.aso.resumo', ['cliente' => $cliente]),
        ]);
    }

    public function update(Request $request, Tarefa $tarefa)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $empresaId = auth()->user()->empresa_id;
        $cliente = $tarefa->cliente;

        $this->normalizeTreinamentosInput($request);

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId);
        $treinamentosPermitidos = $this->getTreinamentosPermitidosAvulsos($empresaId, $cliente->id, $treinamentosDisponiveis);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente->id);
        $codigosPacotes = $this->getTreinamentosCodigosPacotes($pacotesTreinamentos, $treinamentosDisponiveis);
        $treinamentosCodigos = array_values(array_unique(array_merge($treinamentosPermitidos, $codigosPacotes)));
        $temTreinamentosPermitidos = !empty($treinamentosPermitidos) || !empty($pacotesTreinamentos);
        $pacotesIds = collect($pacotesTreinamentos)->pluck('contrato_item_id')->filter()->values()->all();
        $unidadesPermitidasIds = $this->unidadesPermitidasIds($empresaId, $cliente->id);

        $data = $request->validate([
            'funcionario_id' => ['nullable', 'exists:funcionarios,id'],
            'nome' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'rg' => ['nullable', 'string', 'max:255'],
            'celular' => ['nullable', 'string', 'max:30'],
            'funcao_id' => ['nullable', 'integer', 'exists:funcoes,id'],
            'tipo_aso' => ['required', 'in:admissional,periodico,demissional,mudanca_funcao,retorno_trabalho'],
            'data_aso' => ['required', 'date_format:Y-m-d'],
            'unidade_id' => ['required', 'integer', Rule::in($unidadesPermitidasIds)],
            'vai_fazer_treinamento' => ['nullable', 'boolean'],
            'treinamento_modo' => ['nullable', Rule::in(['avulsos', 'pacotes'])],
            'pacote_id' => ['nullable', 'integer', Rule::in($pacotesIds)],
            'treinamentos' => ['array'],
            'treinamentos.*' => ['string', Rule::in($treinamentosCodigos)],
            'email_aso' => ['nullable', 'email'],
            'pcmso_elaborado_formed' => ['required', 'boolean'],
            'anexos' => ['nullable', 'array'],
            'anexos.*' => ['file', 'mimes:pdf,doc,docx,png,jpg,jpeg', 'max:10240'],
        ], $this->mensagensValidacao(), $this->atributosValidacao());

        $this->validarUploadPcmsoExterno($request, $data, $tarefa->asoSolicitacao);

        $tiposAsoPermitidos = $this->tiposAsoPermitidos($cliente->id, $empresaId);
        if (empty($tiposAsoPermitidos)) {
            throw ValidationException::withMessages([
                'tipo_aso' => 'ASO não disponível para este cliente. Fale com seu comercial.',
            ]);
        }
        if (!in_array($data['tipo_aso'], $tiposAsoPermitidos, true)) {
            throw ValidationException::withMessages([
                'tipo_aso' => 'ASO não disponível para este tipo. Fale com seu comercial.',
            ]);
        }

        if (!empty($data['vai_fazer_treinamento']) && !$temTreinamentosPermitidos) {
            throw ValidationException::withMessages([
                'treinamentos' => 'Serviço não contratado converse com seu comercial',
            ]);
        }

        $pacoteSelecionado = $this->resolvePacoteTreinamento($data, $pacotesTreinamentos);

        DB::transaction(function () use ($data, $empresaId, $cliente, $tarefa, $request, $pacoteSelecionado) {

            // FUNCIONÁRIO (reaproveita/atualiza)
            if (!empty($data['funcionario_id'])) {
                $funcionario = Funcionario::where('empresa_id', $empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->with('funcao')
                    ->findOrFail($data['funcionario_id']);
                if (!empty($data['celular'])) {
                    $funcionario->celular = $data['celular'];
                    $funcionario->save();
                }
            } else {
                $funcionario = $tarefa->funcionario ?: new Funcionario([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                ]);

                $funcionario->fill([
                    'nome' => $data['nome'],
                    'cpf' => $data['cpf'] ?? null,
                    'rg' => $data['rg'],
                    'celular' => $data['celular'] ?? null,
                    'data_nascimento' => $data['data_nascimento'],
                    'funcao_id' => $data['funcao_id'] ?? null,
                ]);
                $funcionario->save();
            }

            // monta descrição "bonita"
            $tipoAsoLabel = match ($data['tipo_aso']) {
                'admissional' => 'Admissional',
                'periodico' => 'Periódico',
                'demissional' => 'Demissional',
                'mudanca_funcao' => 'Mudança de Função',
                'retorno_trabalho' => 'Retorno ao Trabalho',
            };

            $descricao = "Tipo: {$tipoAsoLabel}";
            $treinamentos = $data['treinamentos'] ?? [];

            if (
                $data['tipo_aso'] === 'mudanca_funcao'
                && !empty($data['funcionario_id'])
                && !empty($data['funcao_id'])
            ) {
                $funcaoAnteriorNome = optional($funcionario->funcao)->nome ?? 'Não informada';
                $funcionario->funcao_id = $data['funcao_id'];
                $funcionario->save();

                $funcaoNovaNome = Funcao::find($data['funcao_id'])->nome ?? 'Não informada';
                $descricao .= " | Mudança de função: {$funcaoAnteriorNome} → {$funcaoNovaNome}";
            }

            $funcaoIdCheck = $data['funcao_id'] ?? $funcionario->funcao_id;
            $this->assertGheParaFuncao($empresaId, $cliente->id, $funcaoIdCheck);
            $this->assertGheParaFuncaoTipo($empresaId, $cliente->id, $funcaoIdCheck, $data['tipo_aso']);

            if (!empty($data['vai_fazer_treinamento']) && !empty($treinamentos)) {
                $labels = [];
                foreach ($treinamentos as $codigo) {
                    $labels[] = $treinamentosDisponiveis[$codigo] ?? $codigo;
                }
                $descricao .= ' | Treinamentos: ' . implode(', ', $labels);
            }
            if (!empty($data['vai_fazer_treinamento']) && !empty($pacoteSelecionado['nome'])) {
                $descricao .= ' | Pacote: ' . $pacoteSelecionado['nome'];
            }

            $inicioPrevisto = $tarefa->inicio_previsto
                ?: Carbon::createFromFormat('Y-m-d H:i:s', $data['data_aso'] . ' 07:00:00');
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, (int) $tarefa->servico_id);

            // atualiza tarefa
            $tarefa->update([
                'funcionario_id' => $funcionario->id,
                'descricao' => $descricao,
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto' => $fimPrevisto,
            ]);

            // atualiza / cria aso_solicitacao
            $aso = $tarefa->asoSolicitacao ?: new AsoSolicitacoes([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
            ]);

            $anexosNovos = Anexos::salvarDoRequest($request, 'anexos', [
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'tarefa_id'      => $tarefa->id,
                'funcionario_id' => $funcionario->id ?? null,
                'uploaded_by'    => auth()->id(),
                'servico'        => 'ASO',
                'subpath'        => 'anexos-custom/' . $empresaId,
            ]);

            $pcmsoElaboradoFormed = $this->isPcmsoElaboradoPelaFormed($data);
            $pcmsoExternoAnexoId = $aso->pcmso_externo_anexo_id;
            if ($pcmsoElaboradoFormed) {
                $pcmsoExternoAnexoId = null;
            } elseif ($anexosNovos->isNotEmpty()) {
                $pcmsoExternoAnexoId = optional($anexosNovos->first())->id;
            }

            $aso->fill([
                'funcionario_id' => $funcionario->id,
                'unidade_id' => $data['unidade_id'],
                'tipo_aso' => $data['tipo_aso'],
                'data_aso' => $data['data_aso'],
                'email_aso' => $data['email_aso'] ?? null,
                'pcmso_elaborado_formed' => $pcmsoElaboradoFormed,
                'pcmso_externo_anexo_id' => $pcmsoExternoAnexoId,
                'vai_fazer_treinamento' => !empty($data['vai_fazer_treinamento']),
                'treinamentos' => $treinamentos,
                'treinamento_pacote' => !empty($data['vai_fazer_treinamento']) ? $pacoteSelecionado : null,
            ]);

            $aso->save();
        });

        $origem = $request->query('origem');

        if ($origem === 'cliente') {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'ASO atualizado com sucesso.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa ASO atualizada com sucesso.');

    }



    public function asoCreate(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        // Funcionários já cadastrados para esse cliente
        $funcionarios = Funcionario::where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->orderBy('nome')
            ->get();

        // Unidades da clínica
        $unidades = $this->unidadesParaAgendamento($empresaId, $cliente->id);

        // Funções
        $contratoAtivo = app(ContratoClienteService::class)
            ->getContratoAtivo($cliente->id, $empresaId, null);
        if ($contratoAtivo && !$contratoAtivo->relationLoaded('itens')) {
            $contratoAtivo->load('itens');
        }

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaContrato($contratoAtivo, $empresaId);
        if ($funcoes->isEmpty()) {
            $funcoes = app(AsoGheService::class)
                ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);
        }

        // Tipos de ASO
        $tiposAso = [
            'admissional'      => 'Admissional',
            'periodico'        => 'Periódico',
            'demissional'      => 'Demissional',
            'mudanca_funcao'   => 'Mudança de Função',
            'retorno_trabalho' => 'Retorno ao Trabalho',
        ];

        // Treinamentos disponíveis
        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId);
        $treinamentosPermitidos = $this->getTreinamentosPermitidosAvulsos($empresaId, $cliente->id, $treinamentosDisponiveis);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente->id);

        // Valores default para a view em modo "create"
        $treinamentosSelecionados = [];
        $vaiFazerTreinamento      = 0;
        $dataAso                  = null;
        $unidadeSelecionada       = null;

        $tiposAsoPermitidos = $this->tiposAsoPermitidos($cliente->id, $empresaId);

        return view('operacional.kanban.aso.create', [
            'cliente'                  => $cliente,
            'tarefa'                   => null, // importante pra view saber que é create
            'funcionarios'             => $funcionarios,
            'unidades'                 => $unidades,
            'tiposAso'                 => $tiposAso,
            'tiposAsoPermitidos'       => $tiposAsoPermitidos,
            'funcoes'                  => $funcoes,
            'treinamentosDisponiveis'  => $treinamentosDisponiveis,
            'treinamentosPermitidos'   => $treinamentosPermitidos,
            'treinamentosSelecionados' => $treinamentosSelecionados,
            'pacotesTreinamentos'      => $pacotesTreinamentos,
            'vaiFazerTreinamento'      => $vaiFazerTreinamento,
            'dataAso'                  => $dataAso,
            'unidadeSelecionada'       => $unidadeSelecionada,
            'anexos'                   => collect(),
            'asoResumoUrl'             => route('operacional.kanban.aso.resumo', ['cliente' => $cliente]),
        ]);
    }

    private function assertGheParaFuncao(int $empresaId, int $clienteId, ?int $funcaoId): void
    {
        if (!$funcaoId) {
            throw ValidationException::withMessages([
                'funcao_id' => 'Informe a função do colaborador para precificação do ASO via GHE.',
            ]);
        }

        $contratoAtivo = app(ContratoClienteService::class)
            ->getContratoAtivo($clienteId, $empresaId, null);
        if ($contratoAtivo && !$contratoAtivo->relationLoaded('itens')) {
            $contratoAtivo->load('itens');
        }

        $asoService = app(AsoGheService::class);
        $snapshot = $asoService->resolveAsoSnapshotFromContrato($contratoAtivo);
        $map = $snapshot['funcao_ghe_map'] ?? [];

        if (!empty($map)) {
            if (!array_key_exists((string) $funcaoId, $map)) {
                throw ValidationException::withMessages([
                    'ghe' => 'Não existe GHE precificado para a função informada. Ajuste o GHE do cliente antes de criar a solicitação.',
                ]);
            }

            return;
        }

        $temGhe = ClienteGhe::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->exists();

        if (!$temGhe) {
            throw ValidationException::withMessages([
                'ghe' => 'Não é possível criar a solicitação de ASO porque o cliente não possui GHE cadastrado. Cadastre o GHE do cliente e tente novamente.',
            ]);
        }

        $temGheFuncao = ClienteGhe::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->whereHas('funcoes', function ($q) use ($funcaoId) {
                $q->where('funcao_id', $funcaoId);
            })
            ->exists();

        if (!$temGheFuncao) {
            throw ValidationException::withMessages([
                'ghe' => 'Não existe GHE precificado para a função informada. Ajuste o GHE do cliente antes de criar a solicitação.',
            ]);
        }
    }

    private function assertGheParaFuncaoTipo(int $empresaId, int $clienteId, ?int $funcaoId, string $tipoAso): void
    {
        if (!$funcaoId) {
            throw ValidationException::withMessages([
                'funcao_id' => 'Informe a função do colaborador para validar o ASO.',
            ]);
        }

        $contratoAtivo = app(ContratoClienteService::class)
            ->getContratoAtivo($clienteId, $empresaId, null);
        if ($contratoAtivo && !$contratoAtivo->relationLoaded('itens')) {
            $contratoAtivo->load('itens');
        }

        $asoService = app(AsoGheService::class);
        $snapshot = $asoService->resolveAsoSnapshotFromContrato($contratoAtivo);
        if (!$snapshot || empty($snapshot['ghes'])) {
            throw ValidationException::withMessages([
                'ghe' => 'Não foi possível localizar configuração de GHE para este cliente. Entre em contato com o comercial.',
            ]);
        }

        $ghe = $asoService->resolveGheSnapshotByFuncao($snapshot, $funcaoId);
        if (!$ghe) {
            throw ValidationException::withMessages([
                'ghe' => 'Não existe GHE configurado para a função informada. Entre em contato com o comercial.',
            ]);
        }

        $preco = $asoService->resolvePrecoAsoPorFuncaoTipo($contratoAtivo, $funcaoId, $tipoAso);
        if ($preco === null) {
            throw ValidationException::withMessages([
                'ghe' => 'Não existe configuração de ASO para esta função e tipo. Entre em contato com o comercial.',
            ]);
        }
    }

    public function funcionarioDados(Cliente $cliente, Funcionario $funcionario, Request $request)
    {
        $usuario = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);
        abort_if($funcionario->empresa_id !== $empresaId, 403);
        abort_if($funcionario->cliente_id !== $cliente->id, 403);

        return response()->json([
            'ok' => true,
            'funcionario' => [
                'id' => $funcionario->id,
                'nome' => $funcionario->nome,
                'cpf' => $funcionario->cpf,
                'rg' => $funcionario->rg,
                'data_nascimento' => $funcionario->data_nascimento?->format('Y-m-d'),
                'celular' => $funcionario->celular,
                'funcao_id' => $funcionario->funcao_id,
            ],
        ]);
    }

    public function resumo(Request $request, Cliente $cliente)
    {
        $usuario = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'tipo_aso' => ['required', 'in:admissional,periodico,demissional,mudanca_funcao,retorno_trabalho'],
            'funcao_id' => ['required', 'integer', 'exists:funcoes,id'],
        ]);

        $contratoAtivo = app(ContratoClienteService::class)
            ->getContratoAtivo($cliente->id, $empresaId, null);
        if ($contratoAtivo && !$contratoAtivo->relationLoaded('itens')) {
            $contratoAtivo->load('itens');
        }

        if (!$contratoAtivo) {
            return response()->json([
                'ok' => false,
                'message' => 'Cliente sem contrato ativo.',
            ], 422);
        }

        $asoService = app(AsoGheService::class);
        $snapshot = $asoService->resolveAsoSnapshotFromContrato($contratoAtivo);
        if (!$snapshot || empty($snapshot['ghes'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Nenhuma configuração de ASO encontrada no contrato.',
            ], 422);
        }

        $ghe = $asoService->resolveGheSnapshotByFuncao($snapshot, (int) $data['funcao_id']);
        if (!$ghe) {
            return response()->json([
                'ok' => false,
                'message' => 'Não existe GHE precificado para a função informada.',
            ], 422);
        }

        $exames = $asoService->resolveExamesAsoPorFuncaoTipo($contratoAtivo, (int) $data['funcao_id'], $data['tipo_aso']);
        $total = $asoService->resolvePrecoAsoPorFuncaoTipo($contratoAtivo, (int) $data['funcao_id'], $data['tipo_aso']);

        $rateado = (bool) ($ghe['rateado_por_tipo'][$data['tipo_aso']] ?? false);

        return response()->json([
            'ok' => true,
            'ghe' => [
                'id' => $ghe['id'] ?? null,
                'nome' => $ghe['nome'] ?? null,
            ],
            'exames' => $exames,
            'total' => $total,
            'rateado' => $rateado,
        ]);
    }

    private function normalizeTreinamentosInput(Request $request): void
    {
        if (!$request->has('treinamentos')) {
            return;
        }

        $treinamentos = (array) $request->input('treinamentos', []);
        $request->merge([
            'treinamentos' => $this->normalizeTreinamentosValues($treinamentos),
        ]);
    }

    private function normalizeTreinamentosValues(array $treinamentos): array
    {
        $normalized = [];
        foreach ($treinamentos as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if (preg_match('/^nr[_-]?(\\d+)$/i', $value, $m)) {
                $numero = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $normalized[] = 'NR-' . $numero;
                continue;
            }

            $normalized[] = $value;
        }

        return array_values(array_unique($normalized));
    }

    private function isPcmsoElaboradoPelaFormed(array $data): bool
    {
        $value = $data['pcmso_elaborado_formed'] ?? true;
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    private function validarUploadPcmsoExterno(
        Request $request,
        array $data,
        ?AsoSolicitacoes $asoExistente = null
    ): void {
        if ($this->isPcmsoElaboradoPelaFormed($data)) {
            return;
        }

        $temArquivoNovo = $request->hasFile('anexos');
        $temArquivoExistente = $asoExistente && !empty($asoExistente->pcmso_externo_anexo_id);

        if (!$temArquivoNovo && !$temArquivoExistente) {
            throw ValidationException::withMessages([
                'anexos' => 'Envie o PCMSO externo quando ele não for elaborado pela Formed.',
            ]);
        }
    }

    private function resolvePacoteTreinamento(array $data, array $pacotesTreinamentos): ?array
    {
        $modo = $data['treinamento_modo'] ?? null;
        if (empty($data['vai_fazer_treinamento']) || $modo !== 'pacotes') {
            return null;
        }

        $pacoteId = (int) ($data['pacote_id'] ?? 0);
        if ($pacoteId <= 0) {
            return null;
        }

        $pacote = collect($pacotesTreinamentos)->firstWhere('contrato_item_id', $pacoteId);
        if (!$pacote) {
            return null;
        }

        return [
            'contrato_item_id' => $pacote['contrato_item_id'] ?? null,
            'nome' => $pacote['nome'] ?? 'Pacote de Treinamentos',
            'descricao' => $pacote['descricao'] ?? null,
            'valor' => $pacote['valor'] ?? 0,
            'codigos' => $pacote['codigos'] ?? [],
        ];
    }

    private function getTreinamentosDisponiveis(int $empresaId): array
    {
        $treinamentoServicoId = $this->treinamentoServicoId($empresaId);

        if ($treinamentoServicoId <= 0) {
            return [];
        }

        $padrao = TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->first();

        if (!$padrao) {
            return [];
        }

        return TabelaPrecoItem::query()
            ->where('tabela_preco_padrao_id', $padrao->id)
            ->where('servico_id', $treinamentoServicoId)
            ->where('ativo', true)
            ->orderBy('codigo')
            ->get()
            ->mapWithKeys(function ($item) {
                $label = trim(($item->codigo ?? '') . ' - ' . ($item->descricao ?? ''));
                return [$item->codigo => $label ?: $item->codigo];
            })
            ->all();
    }

    private function tabelaClienteAtiva(int $empresaId, int $clienteId): ?ClienteTabelaPreco
    {
        if ($clienteId <= 0) {
            return null;
        }

        return ClienteTabelaPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where('ativa', true)
            ->first();
    }

    private function treinamentoServicoId(int $empresaId): ?int
    {
        $id = (int) (config('services.treinamento_id') ?? 0);
        if ($id > 0) {
            return $id;
        }

        return Servico::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where(function ($q) {
                $q->whereRaw('LOWER(tipo) = ?', ['treinamento'])
                    ->orWhereRaw('LOWER(nome) like ?', ['%treinamento%']);
            })
            ->orderBy('id')
            ->value('id');
    }

    private function getTreinamentosPermitidosAvulsos(int $empresaId, int $clienteId, array $treinamentosDisponiveis): array
    {
        if (empty($treinamentosDisponiveis)) {
            return [];
        }

        $treinamentoServicoId = $this->treinamentoServicoId($empresaId);
        if (!$treinamentoServicoId) {
            return [];
        }

        $contrato = app(ContratoClienteService::class)->getContratoAtivo($clienteId, $empresaId, null);
        if (!$contrato) {
            return [];
        }

        $contrato->loadMissing('parametroOrigem.itens');
        $itensOrigem = $contrato->parametroOrigem?->itens ?? collect();
        if ($itensOrigem->isEmpty()) {
            return [];
        }

        $codigos = $itensOrigem
            ->filter(function ($item) {
                $tipo = strtoupper((string) ($item->tipo ?? ''));
                return $tipo === 'TREINAMENTO_NR';
            })
            ->flatMap(function ($item) {
                $codigo = $item->meta['codigo'] ?? null;
                if (!$codigo) {
                    $nome = (string) ($item->nome ?? '');
                    if ($nome !== '' && preg_match('/^(NR[-\\s]?\\d+[A-Z]?)/i', $nome, $m)) {
                        $codigo = str_replace(' ', '-', $m[1]);
                    }
                }

                return $codigo ? [$codigo] : [];
            })
            ->map(fn ($codigo) => trim((string) $codigo))
            ->filter()
            ->values()
            ->all();

        if (empty($codigos)) {
            return [];
        }

        $codigos = $this->normalizeTreinamentosValues($codigos);
        $disponiveis = array_keys($treinamentosDisponiveis);

        return array_values(array_intersect($disponiveis, $codigos));
    }

    private function getTreinamentosCodigosPacotes(array $pacotesTreinamentos, array $treinamentosDisponiveis): array
    {
        if (empty($pacotesTreinamentos) || empty($treinamentosDisponiveis)) {
            return [];
        }

        $codigos = collect($pacotesTreinamentos)
            ->flatMap(fn ($pacote) => (array) ($pacote['codigos'] ?? []))
            ->map(fn ($codigo) => trim((string) $codigo))
            ->filter()
            ->values()
            ->all();

        if (empty($codigos)) {
            return [];
        }

        $codigos = $this->normalizeTreinamentosValues($codigos);
        $disponiveis = array_keys($treinamentosDisponiveis);

        return array_values(array_intersect($disponiveis, $codigos));
    }

    private function getPacotesTreinamentos(int $empresaId, int $clienteId): array
    {
        $contrato = app(ContratoClienteService::class)->getContratoAtivo($clienteId, $empresaId, null);
        if (!$contrato) {
            return [];
        }

        $contrato->loadMissing('itens', 'parametroOrigem.itens');
        $itensOrigem = $contrato->parametroOrigem?->itens ?? collect();
        if ($itensOrigem->isEmpty()) {
            return [];
        }

        $pacotes = [];
        $pacotesOrigem = $itensOrigem
            ->filter(fn ($it) => strtoupper((string) ($it->tipo ?? '')) === 'PACOTE_TREINAMENTOS')
            ->values();

        $treinamentoServicoId = $this->treinamentoServicoId($empresaId);
        foreach ($pacotesOrigem as $item) {
            $descricao = trim((string) ($item->descricao ?? $item->nome ?? ''));
            $treinamentosMeta = (array) ($item->meta['treinamentos'] ?? []);
            $codigos = collect($treinamentosMeta)
                ->map(fn ($trein) => $trein['codigo'] ?? null)
                ->filter()
                ->values()
                ->all();
            $codigos = $this->normalizeTreinamentosValues($codigos);

            $contratoItem = $contrato->itens
                ->first(function ($it) use ($treinamentoServicoId, $descricao) {
                    return (int) $it->servico_id === (int) $treinamentoServicoId
                        && trim((string) ($it->descricao_snapshot ?? '')) === $descricao;
                });

            if (!$contratoItem) {
                $contratoItem = $contrato->itens
                    ->first(fn ($it) => (int) $it->servico_id === (int) $treinamentoServicoId);
            }

            $pacotes[] = [
                'contrato_item_id' => $contratoItem?->id,
                'nome' => (string) ($item->nome ?? 'Pacote de Treinamentos'),
                'descricao' => $descricao,
                'codigos' => $codigos,
                'valor' => (float) ($contratoItem?->preco_unitario_snapshot ?? 0),
            ];
        }

        return array_values($pacotes);
    }

    private function unidadesParaAgendamento(int $empresaId, int $clienteId, ?int $incluirUnidadeId = null)
    {
        $permitidasIds = ClienteUnidadePermitida::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->pluck('unidade_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $query = UnidadeClinica::query()->where('empresa_id', $empresaId);
        if (!empty($permitidasIds)) {
            $query->whereIn('id', $permitidasIds);
        }

        $unidades = $query->orderBy('nome')->get();

        if ($incluirUnidadeId && !$unidades->contains(fn ($u) => (int) $u->id === (int) $incluirUnidadeId)) {
            $extra = UnidadeClinica::query()
                ->where('empresa_id', $empresaId)
                ->where('id', $incluirUnidadeId)
                ->first();
            if ($extra) {
                $unidades->push($extra);
                $unidades = $unidades->sortBy('nome')->values();
            }
        }

        return $unidades;
    }

    private function unidadesPermitidasIds(int $empresaId, int $clienteId): array
    {
        $permitidasIds = ClienteUnidadePermitida::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->pluck('unidade_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (!empty($permitidasIds)) {
            return $permitidasIds;
        }

        return UnidadeClinica::query()
            ->where('empresa_id', $empresaId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function tiposAsoPermitidos(int $clienteId, int $empresaId): array
    {
        $contrato = app(ContratoClienteService::class)->getContratoAtivo($clienteId, $empresaId, null);
        if ($contrato && !$contrato->relationLoaded('itens')) {
            $contrato->load('itens');
        }

        return app(AsoGheService::class)->resolveTiposAsoContrato($contrato);
    }

    private function mensagensValidacao(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto válido.',
            'max' => 'O campo :attribute deve ter no máximo :max caracteres.',
            'email' => 'Informe um e-mail válido.',
            'date' => 'Informe uma data válida para :attribute.',
            'date_format' => 'Informe a data de :attribute no formato correto.',
            'exists' => 'O valor selecionado para :attribute é inválido.',
            'in' => 'O valor selecionado para :attribute é inválido.',
            'array' => 'O campo :attribute deve ser uma lista válida.',
            'boolean' => 'O campo :attribute deve ser sim ou não.',
            'file' => 'Envie um arquivo válido para :attribute.',
            'mimes' => 'O arquivo de :attribute deve ser PDF, DOC, DOCX, PNG, JPG ou JPEG.',
        ];
    }

    private function atributosValidacao(): array
    {
        return [
            'funcionario_id' => 'funcionário',
            'nome' => 'nome',
            'cpf' => 'CPF',
            'rg' => 'RG',
            'data_nascimento' => 'data de nascimento',
            'funcao_id' => 'função',
            'tipo_aso' => 'tipo de ASO',
            'data_aso' => 'data do ASO',
            'unidade_id' => 'unidade',
            'vai_fazer_treinamento' => 'vai fazer treinamento',
            'treinamentos' => 'treinamentos',
            'treinamentos.*' => 'treinamento',
            'email_aso' => 'e-mail para envio do ASO',
            'pcmso_elaborado_formed' => 'PCMSO elaborado pela Formed',
            'anexos' => 'anexos',
            'anexos.*' => 'arquivo anexo',
        ];
    }



}
