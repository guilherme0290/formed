<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Anexos;
use App\Models\AsoSolicitacoes;
use App\Models\Cliente;
use App\Models\ClienteGhe;
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
use App\Services\AsoGheService;
use App\Services\ContratoClienteService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AsoController extends Controller
{
    public function asoStore(Cliente $cliente, Request $request)
    {
        $usuario = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $this->normalizeTreinamentosInput($request);

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId);
        $treinamentosPermitidos = $this->getTreinamentosPermitidos($empresaId, $cliente->id, $treinamentosDisponiveis);
        $treinamentosCodigos = $treinamentosPermitidos;

        $data = $request->validate([
            'funcionario_id' => ['nullable', 'exists:funcionarios,id'],
            'nome' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'rg' => ['nullable', 'string', 'max:255'],
            'funcao_id' => ['nullable', 'integer', 'exists:funcoes,id'],
            'tipo_aso' => ['required', 'in:admissional,periodico,demissional,mudanca_funcao,retorno_trabalho'],
            'data_aso' => ['required', 'date_format:Y-m-d'],
            'unidade_id' => ['required', 'exists:unidades_clinicas,id'],
            'vai_fazer_treinamento' => ['nullable', 'boolean'],
            'treinamentos' => ['array'],
            'treinamentos.*' => ['string', Rule::in($treinamentosCodigos)],
            'email_aso' => ['nullable', 'email'],
        ], $this->mensagensValidacao(), $this->atributosValidacao());

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

        if (!empty($data['vai_fazer_treinamento']) && empty($treinamentosPermitidos)) {
            throw ValidationException::withMessages([
                'treinamentos' => 'Serviço não contratado converse com seu comercial',
            ]);
        }

        $tarefa = DB::transaction(function () use ($data, $empresaId, $cliente, $usuario,$request) {

            // 1) Resolve funcionário (existente ou novo)
            if (!empty($data['funcionario_id'])) {
                $funcionario = Funcionario::where('empresa_id', $empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->with('funcao')
                    ->findOrFail($data['funcionario_id']);
            } else {
                $funcionario = Funcionario::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'nome' => $data['nome'],
                    'cpf' => $data['cpf'] ?? null,
                    'rg' => $data['rg'],
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

            $this->assertGheParaFuncao($empresaId, $cliente->id, $funcionario->funcao_id);

            if (!empty($data['vai_fazer_treinamento']) && !empty($data['treinamentos'])) {
                $labels = [];
                foreach ($data['treinamentos'] as $codigo) {
                    $labels[] = $treinamentosDisponiveis[$codigo] ?? $codigo;
                }
                $descricao .= ' | Treinamentos: ' . implode(', ', $labels);
            }

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
                'inicio_previsto' => $data['data_aso'],
            ]);

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
                'vai_fazer_treinamento' => !empty($data['vai_fazer_treinamento']),
                'treinamentos' => $data['treinamentos'] ?? [],
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

            Anexos::salvarDoRequest($request, 'anexos', [
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'tarefa_id'      => $tarefa->id,
                'funcionario_id' => $funcionario->id ?? null,
                'uploaded_by'    => $usuario->id,
                'servico'        => 'ASO',
                 'subpath'     => 'anexos-custom/' . $empresaId, // opcional, se quiser sobrescrever
            ]);

            return $tarefa;
        });

        $origem = $request->query('origem');

        if ($origem === 'cliente') {
            return redirect()
                ->route('cliente.dashboard')
                ->with('ok', "Agendamento ASO criado com sucesso para {$tarefa->titulo}.");
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', "Tarefa ASO agendada {$tarefa->titulo}.");
    }

    public function edit(Tarefa $tarefa)
    {
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

        $unidades = \App\Models\UnidadeClinica::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);

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
        $treinamentosPermitidos = $this->getTreinamentosPermitidos($empresaId, $cliente->id, $treinamentosDisponiveis);
        $treinamentosPermitidos = array_values(array_unique(array_merge(
            $treinamentosPermitidos,
            $treinamentosSelecionados
        )));

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
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Tarefa $tarefa)
    {
        $empresaId = auth()->user()->empresa_id;
        $cliente = $tarefa->cliente;

        $this->normalizeTreinamentosInput($request);

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId);
        $treinamentosPermitidos = $this->getTreinamentosPermitidos($empresaId, $cliente->id, $treinamentosDisponiveis);
        $treinamentosCodigos = $treinamentosPermitidos;

        $data = $request->validate([
            'funcionario_id' => ['nullable', 'exists:funcionarios,id'],
            'nome' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'rg' => ['nullable', 'string', 'max:255'],
            'funcao_id' => ['nullable', 'integer', 'exists:funcoes,id'],
            'tipo_aso' => ['required', 'in:admissional,periodico,demissional,mudanca_funcao,retorno_trabalho'],
            'data_aso' => ['required', 'date_format:Y-m-d'],
            'unidade_id' => ['required', 'exists:unidades_clinicas,id'],
            'vai_fazer_treinamento' => ['nullable', 'boolean'],
            'treinamentos' => ['array'],
            'treinamentos.*' => ['string', Rule::in($treinamentosCodigos)],
            'email_aso' => ['nullable', 'email'],
        ], $this->mensagensValidacao(), $this->atributosValidacao());

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

        if (!empty($data['vai_fazer_treinamento']) && empty($treinamentosPermitidos)) {
            throw ValidationException::withMessages([
                'treinamentos' => 'Serviço não contratado converse com seu comercial',
            ]);
        }

        DB::transaction(function () use ($data, $empresaId, $cliente, $tarefa, $request) {

            // FUNCIONÁRIO (reaproveita/atualiza)
            if (!empty($data['funcionario_id'])) {
                $funcionario = Funcionario::where('empresa_id', $empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->with('funcao')
                    ->findOrFail($data['funcionario_id']);
            } else {
                $funcionario = $tarefa->funcionario ?: new Funcionario([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                ]);

                $funcionario->fill([
                    'nome' => $data['nome'],
                    'cpf' => $data['cpf'] ?? null,
                    'rg' => $data['rg'],
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

            $this->assertGheParaFuncao($empresaId, $cliente->id, $funcionario->funcao_id);

            if (!empty($data['vai_fazer_treinamento']) && !empty($treinamentos)) {
                $labels = [];
                foreach ($treinamentos as $codigo) {
                    $labels[] = $treinamentosDisponiveis[$codigo] ?? $codigo;
                }
                $descricao .= ' | Treinamentos: ' . implode(', ', $labels);
            }

            // atualiza tarefa
            $tarefa->update([
                'funcionario_id' => $funcionario->id,
                'descricao' => $descricao,
                'inicio_previsto' => $data['data_aso'],
            ]);

            // atualiza / cria aso_solicitacao
            $aso = $tarefa->asoSolicitacao ?: new AsoSolicitacoes([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
            ]);

            $aso->fill([
                'funcionario_id' => $funcionario->id,
                'unidade_id' => $data['unidade_id'],
                'tipo_aso' => $data['tipo_aso'],
                'data_aso' => $data['data_aso'],
                'email_aso' => $data['email_aso'] ?? null,
                'vai_fazer_treinamento' => !empty($data['vai_fazer_treinamento']),
                'treinamentos' => $treinamentos,
            ]);

            Anexos::salvarDoRequest($request, 'anexos', [
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'tarefa_id'      => $tarefa->id,
                'funcionario_id' => $funcionario->id ?? null,
                'uploaded_by'    => auth()->user()->id,
                'servico'        => 'ASO',
                'subpath'     => 'anexos-custom/' . $empresaId, // opcional, se quiser sobrescrever
            ]);

            $aso->save();
        });

        $origem = $request->query('origem');

        if ($origem === 'cliente') {
            return redirect()
                ->route('cliente.dashboard')
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
        $unidades = \App\Models\UnidadeClinica::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        // Funções
        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);

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
        $treinamentosPermitidos = $this->getTreinamentosPermitidos($empresaId, $cliente->id, $treinamentosDisponiveis);

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
            'vaiFazerTreinamento'      => $vaiFazerTreinamento,
            'dataAso'                  => $dataAso,
            'unidadeSelecionada'       => $unidadeSelecionada,
            'anexos'                   => collect(),
        ]);
    }

    private function assertGheParaFuncao(int $empresaId, int $clienteId, ?int $funcaoId): void
    {
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

        if (!$funcaoId) {
            throw ValidationException::withMessages([
                'funcao_id' => 'Informe a função do colaborador para precificação do ASO via GHE.',
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

    private function getTreinamentosPermitidos(int $empresaId, int $clienteId, array $treinamentosDisponiveis): array
    {
        if (empty($treinamentosDisponiveis)) {
            return [];
        }

        $treinamentoServicoId = $this->treinamentoServicoId($empresaId);
        if (!$treinamentoServicoId) {
            return [];
        }

        $tabelaCliente = $this->tabelaClienteAtiva($empresaId, $clienteId);
        if (!$tabelaCliente) {
            return [];
        }

        $codigos = ClienteTabelaPrecoItem::query()
            ->where('cliente_tabela_preco_id', $tabelaCliente->id)
            ->where('servico_id', $treinamentoServicoId)
            ->where('ativo', true)
            ->whereNotNull('codigo')
            ->orderBy('codigo')
            ->pluck('codigo')
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
        ];
    }



}
