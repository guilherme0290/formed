<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Operacional\Concerns\ValidatesClientePortalTaskEditing;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteUnidadePermitida;
use App\Services\AsoGheService;
use App\Models\Funcionario;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Models\TreinamentoNR;
use App\Models\TreinamentoNrDetalhes;
use App\Models\UnidadeClinica;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Services\TempoTarefaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TreinamentoNrController extends Controller
{
    use ValidatesClientePortalTaskEditing;

    public function create(Cliente $cliente)
    {
        $user = Auth::user();
        $empresaId = $user->empresa_id;

        // Funcionários já vinculados ao cliente
        $funcionarios = Funcionario::where('cliente_id', $cliente->id)
            ->orderBy('nome')
            ->get();

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);

        // Unidades da FORMED (ou o que fizer sentido aí)
        $unidades = $this->unidadesParaAgendamento($empresaId, $cliente->id);

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId, $cliente);
        $contratoAtivo = $this->contratoAtivo($cliente);
        $treinamentosFinalizados = $this->getTreinamentosFinalizados($empresaId, $cliente, $contratoAtivo);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente, $contratoAtivo);

        return view('operacional.kanban.treinamentos-nr.create', [
            'cliente'      => $cliente,
            'funcionarios' => $funcionarios,
            'unidades'     => $unidades,
            'funcoes'      => $funcoes,
            'user'         => $user,
            'tarefa'       => null,
            'detalhes'     => null,
            'selecionados' => [],
            'treinamentosDisponiveis' => $treinamentosDisponiveis,
            'treinamentosFinalizados' => $treinamentosFinalizados,
            'pacotesTreinamentos' => $pacotesTreinamentos,
            'isEdit'       => false,
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id;

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId, $cliente);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente, $this->contratoAtivo($cliente));
        $pacotesIds = collect($pacotesTreinamentos)->pluck('contrato_item_id')->filter()->values()->all();
        $treinamentosCodigos = $treinamentosDisponiveis->pluck('codigo')->filter()->values()->all();
        $unidadesPermitidasIds = $this->unidadesPermitidasIds($empresaId, $cliente->id);

        if (empty($treinamentosCodigos) && empty($pacotesIds)) {
            return back()
                ->withErrors(['treinamentos' => 'Nenhum treinamento contratado para este cliente.'])
                ->withInput();
        }

        $data = $request->validate([
            'funcionarios'   => ['required', 'array', 'min:1'],
            'funcionarios.*' => ['integer', 'exists:funcionarios,id'],
            'treinamento_modo' => ['required', Rule::in(['avulso', 'pacote'])],
            'treinamentos'   => ['required_if:treinamento_modo,avulso', 'array', 'min:1'],
            'treinamentos.*' => ['string', Rule::in($treinamentosCodigos)],
            'pacote_id' => ['required_if:treinamento_modo,pacote', 'nullable', 'integer', Rule::in($pacotesIds)],
            'local_tipo' => ['required', 'in:clinica,empresa'],
            'unidade_id' => ['required_if:local_tipo,clinica', 'nullable', 'integer', Rule::in($unidadesPermitidasIds)],
        ], [
            'funcionarios.required' => 'Selecione pelo menos um participante.',
            'funcionarios.min' => 'Selecione pelo menos um participante.',
            'funcionarios.array' => 'Selecione pelo menos um participante.',
            'treinamentos.required' => 'Selecione pelo menos um treinamento.',
            'treinamentos.min' => 'Selecione pelo menos um treinamento.',
            'treinamentos.array' => 'Selecione pelo menos um treinamento.',
            'treinamento_modo.required' => 'Selecione o tipo de treinamento: avulso ou pacote.',
            'treinamento_modo.in' => 'Selecione um tipo de treinamento válido.',
            'pacote_id.required_if' => 'Selecione um pacote de treinamentos.',
            'pacote_id.in' => 'Selecione um pacote de treinamentos válido.',
            'local_tipo.required' => 'Selecione onde o treinamento será realizado.',
            'local_tipo.in' => 'Selecione um local válido para o treinamento.',
            'unidade_id.required_if' => 'Selecione a unidade credenciada quando o local for Na Clínica.',
            'unidade_id.in' => 'Selecione uma unidade credenciada válida.',
            'unidade_id.integer' => 'Selecione uma unidade credenciada válida.',
        ]);

        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        $servicoTreinamentosNr = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'Treinamentos NRs')
            ->first();
        $tipoLabel = 'Treinamentos de NRs';
        $tarefasCriadas = 0;

        Log::info('treinamentos_nr.store.payload', [
            'cliente_id' => (int) $cliente->id,
            'modo' => $data['treinamento_modo'] ?? null,
            'funcionarios' => array_values((array) ($data['funcionarios'] ?? [])),
            'pacote_id' => $data['pacote_id'] ?? null,
            'treinamentos' => array_values((array) ($data['treinamentos'] ?? [])),
        ]);

        DB::transaction(function () use (
            $data,
            $cliente,
            $usuario,
            $empresaId,
            $colunaInicial,
            $servicoTreinamentosNr,
            $tipoLabel,
            $pacotesTreinamentos,
            &$tarefasCriadas
        ) {
            $unidadeNome = null;
            if ($data['local_tipo'] === 'clinica' && !empty($data['unidade_id'])) {
                $unidadeNome = UnidadeClinica::query()
                    ->where('empresa_id', $empresaId)
                    ->find($data['unidade_id'])?->nome;
            }

            $treinamentosPayload = $this->buildTreinamentosPayload($data, $pacotesTreinamentos);
            $funcionariosIds = array_values(array_unique(array_map('intval', (array) ($data['funcionarios'] ?? []))));
            $isPacote = ($data['treinamento_modo'] ?? null) === 'pacote';

            Log::info('treinamentos_nr.store.resolved', [
                'cliente_id' => (int) $cliente->id,
                'is_pacote' => $isPacote,
                'funcionarios_count' => count($funcionariosIds),
                'funcionarios_ids' => $funcionariosIds,
            ]);

            // Regra solicitada: pacote com vários participantes gera 1 tarefa por participante.
            if ($isPacote && count($funcionariosIds) > 1) {
                $funcionariosMap = Funcionario::query()
                    ->whereIn('id', $funcionariosIds)
                    ->get()
                    ->keyBy('id');

                foreach ($funcionariosIds as $funcionarioId) {
                    $inicioPrevisto = now();
                    $fimPrevisto = app(TempoTarefaService::class)
                        ->calcularFimPrevisto($inicioPrevisto, $empresaId, optional($servicoTreinamentosNr)->id);

                    $funcionarioNome = $funcionariosMap->get($funcionarioId)?->nome;

                    $tarefa = Tarefa::create([
                        'empresa_id'      => $empresaId,
                        'cliente_id'      => $cliente->id,
                        'responsavel_id'  => $usuario->id,
                        'coluna_id'       => optional($colunaInicial)->id,
                        'servico_id'      => optional($servicoTreinamentosNr)->id,
                        'funcionario_id'  => $funcionarioId ?: null,
                        'titulo'          => $funcionarioNome
                            ? "Treinamento NR - {$funcionarioNome}"
                            : "Treinamento NR",
                        'descricao'       => "Treinamento NR - {$tipoLabel} · Local: {$data['local_tipo']}"
                            . ($data['local_tipo'] === 'clinica'
                                ? " · Unidade: " . ($unidadeNome ?: '—')
                                : ' · In Company'),
                        'inicio_previsto' => $inicioPrevisto,
                        'fim_previsto'    => $fimPrevisto,
                    ]);

                    TreinamentoNR::create([
                        'tarefa_id'      => $tarefa->id,
                        'funcionario_id' => $funcionarioId,
                    ]);

                    TreinamentoNrDetalhes::create([
                        'tarefa_id'  => $tarefa->id,
                        'local_tipo' => $data['local_tipo'],
                        'unidade_id' => $data['unidade_id'] ?? null,
                        'treinamentos' => $treinamentosPayload,
                    ]);

                    $tarefasCriadas++;
                }

                return;
            }

            // Fluxo atual para avulso (ou pacote com 1 participante): 1 tarefa com os participantes.
            $inicioPrevisto = now();
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, optional($servicoTreinamentosNr)->id);

            $tarefa = Tarefa::create([
                'empresa_id'      => $empresaId,
                'cliente_id'      => $cliente->id,
                'responsavel_id'  => $usuario->id,
                'coluna_id'       => optional($colunaInicial)->id,
                'servico_id'      => optional($servicoTreinamentosNr)->id,
                'titulo'          => "Treinamento NR",
                'descricao'       => "Treinamento NR - {$tipoLabel} · Local: {$data['local_tipo']}"
                    . ($data['local_tipo'] === 'clinica'
                        ? " · Unidade: " . ($unidadeNome ?: '—')
                        : ' · In Company'),
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto'    => $fimPrevisto,
            ]);

            foreach ($funcionariosIds as $funcionarioId) {
                TreinamentoNR::create([
                    'tarefa_id'      => $tarefa->id,
                    'funcionario_id' => $funcionarioId,
                ]);
            }

            TreinamentoNrDetalhes::create([
                'tarefa_id'  => $tarefa->id,
                'local_tipo' => $data['local_tipo'],
                'unidade_id' => $data['unidade_id'] ?? null,
                'treinamentos' => $treinamentosPayload,
            ]);

            $tarefasCriadas = 1;
        });

        if (method_exists($usuario, 'isCliente') && $usuario->isCliente()) {
            Log::info('treinamentos_nr.store.result', [
                'cliente_id' => (int) $cliente->id,
                'tarefas_criadas' => (int) $tarefasCriadas,
            ]);
            $mensagem = $tarefasCriadas > 1
                ? "Solicitações de Treinamento de NRs criadas com sucesso ({$tarefasCriadas} tarefas) e enviadas para análise."
                : 'Solicitação de Treinamento de NRs criada com sucesso e enviada para análise.';

            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', $mensagem);
        }

        $mensagem = $tarefasCriadas > 1
            ? "Tarefas de Treinamento de NRs criadas com sucesso ({$tarefasCriadas} tarefas)."
            : 'Tarefa de Treinamento de NRs criada com sucesso.';

        Log::info('treinamentos_nr.store.result', [
            'cliente_id' => (int) $cliente->id,
            'tarefas_criadas' => (int) $tarefasCriadas,
        ]);

        return redirect()
            ->route('operacional.painel')
            ->with('ok', $mensagem);
    }

    public function storeFuncionario(Cliente $cliente, Request $request)
    {

        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id;

        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:255'],
            'cpf'       => ['required', 'string', 'max:20'],
            'nascimento'=> ['nullable', 'date'],
            'funcao_id' => ['required', 'integer', 'exists:funcoes,id'],
        ]);


        $funcionario = Funcionario::create([
            'empresa_id'  => $empresaId,
            'cliente_id'  => $cliente->id,
            'nome'        => $data['nome'],
            'cpf'         => $data['cpf'],
            'data_nascimento'  => $data['nascimento'] ?? null,
            'funcao_id'   => $data['funcao_id'],
        ]);

        return response()->json([
            'ok' => true,
            'funcionario' => [
                'id' => $funcionario->id,
                'nome' => $funcionario->nome,
                'cpf'         => $funcionario->cpf,
                'nascimento' => $funcionario->data_nascimento,
                'funcao_nome' => optional($funcionario->funcao)->nome,
            ],
        ], 201);
    }

    public function edit(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $cliente = $tarefa->cliente;

        // participantes
        $selecionados = TreinamentoNR::where('tarefa_id', $tarefa->id)
            ->pluck('funcionario_id')
            ->toArray();

        // detalhes de local
        $detalhes = TreinamentoNrDetalhes::where('tarefa_id', $tarefa->id)->first();

        // lista de funcionários do cliente
        $funcionarios = Funcionario::where('cliente_id', $cliente->id)
            ->orderBy('nome')
            ->get();

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);

        $unidadeSelecionada = $detalhes?->unidade_id ? (int) $detalhes->unidade_id : null;
        $unidades = $this->unidadesParaAgendamento($empresaId, $cliente->id, $unidadeSelecionada);

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId, $cliente);
        $contratoAtivo = $this->contratoAtivo($cliente);
        $treinamentosFinalizados = $this->getTreinamentosFinalizados($empresaId, $cliente, $contratoAtivo);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente, $contratoAtivo);

        return view('operacional.kanban.treinamentos-nr.create', [
            'cliente'      => $cliente,
            'funcionarios' => $funcionarios,
            'unidades'     => $unidades,
            'funcoes'      => $funcoes,
            'user'         => $usuario,
            'tarefa'       => $tarefa,
            'detalhes'     => $detalhes,
            'selecionados' => $selecionados,
            'treinamentosDisponiveis' => $treinamentosDisponiveis,
            'treinamentosFinalizados' => $treinamentosFinalizados,
            'pacotesTreinamentos' => $pacotesTreinamentos,
            'isEdit'       => true,
        ]);
    }

    /**
     * Atualizar Treinamento de NRs
     */
    public function update(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $cliente = $tarefa->cliente;

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId, $cliente);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente, $this->contratoAtivo($cliente));
        $pacotesIds = collect($pacotesTreinamentos)->pluck('contrato_item_id')->filter()->values()->all();
        $treinamentosCodigos = $treinamentosDisponiveis->pluck('codigo')->filter()->values()->all();
        $unidadesPermitidasIds = $this->unidadesPermitidasIds($empresaId, $cliente->id);
        if (empty($treinamentosCodigos) && empty($pacotesIds)) {
            return back()
                ->withErrors(['treinamentos' => 'Nenhum treinamento contratado para este cliente.'])
                ->withInput();
        }

        $data = $request->validate([
            'funcionarios'   => ['required', 'array', 'min:1'],
            'funcionarios.*' => ['integer', 'exists:funcionarios,id'],
            'treinamento_modo' => ['required', Rule::in(['avulso', 'pacote'])],
            'treinamentos'   => ['required_if:treinamento_modo,avulso', 'array', 'min:1'],
            'treinamentos.*' => ['string', Rule::in($treinamentosCodigos)],
            'pacote_id' => ['required_if:treinamento_modo,pacote', 'nullable', 'integer', Rule::in($pacotesIds)],

            'local_tipo' => ['required', 'in:clinica,empresa'],
            'unidade_id' => ['required_if:local_tipo,clinica', 'nullable', 'integer', Rule::in($unidadesPermitidasIds)],
        ], [
            'funcionarios.required' => 'Selecione pelo menos um participante.',
            'funcionarios.min' => 'Selecione pelo menos um participante.',
            'funcionarios.array' => 'Selecione pelo menos um participante.',
            'treinamentos.required' => 'Selecione pelo menos um treinamento.',
            'treinamentos.min' => 'Selecione pelo menos um treinamento.',
            'treinamentos.array' => 'Selecione pelo menos um treinamento.',
            'treinamento_modo.required' => 'Selecione o tipo de treinamento: avulso ou pacote.',
            'treinamento_modo.in' => 'Selecione um tipo de treinamento válido.',
            'pacote_id.required_if' => 'Selecione um pacote de treinamentos.',
            'pacote_id.in' => 'Selecione um pacote de treinamentos válido.',
            'local_tipo.required' => 'Selecione onde o treinamento será realizado.',
            'local_tipo.in' => 'Selecione um local válido para o treinamento.',
            'unidade_id.required_if' => 'Selecione a unidade credenciada quando o local for Na Clínica.',
            'unidade_id.in' => 'Selecione uma unidade credenciada válida.',
            'unidade_id.integer' => 'Selecione uma unidade credenciada válida.',
        ]);

        DB::transaction(function () use ($data, $tarefa, $usuario, $pacotesTreinamentos) {
            $unidadeNome = null;
            if ($data['local_tipo'] === 'clinica' && !empty($data['unidade_id'])) {
                $unidadeNome = UnidadeClinica::query()
                    ->where('empresa_id', $tarefa->empresa_id)
                    ->find($data['unidade_id'])?->nome;
            }

            $funcionariosIds = array_values(array_unique(array_map('intval', (array) ($data['funcionarios'] ?? []))));
            $isPacote = ($data['treinamento_modo'] ?? null) === 'pacote';
            $treinamentosPayload = $this->buildTreinamentosPayload($data, $pacotesTreinamentos);

            // Se editar para pacote com múltiplos participantes:
            // mantém a tarefa atual para o primeiro e cria novas para os demais.
            if ($isPacote && count($funcionariosIds) > 1) {
                $funcionariosMap = Funcionario::query()
                    ->whereIn('id', $funcionariosIds)
                    ->get()
                    ->keyBy('id');

                $primeiroFuncionarioId = (int) array_shift($funcionariosIds);
                $primeiroNome = $funcionariosMap->get($primeiroFuncionarioId)?->nome;

                $tarefa->update([
                    'funcionario_id' => $primeiroFuncionarioId ?: null,
                    'titulo' => $primeiroNome
                        ? "Treinamento NR - {$primeiroNome}"
                        : "Treinamento NR",
                    'descricao' => "Treinamento NR · Local: {$data['local_tipo']}" .
                        ($data['local_tipo'] === 'clinica'
                            ? " · Unidade: " . ($unidadeNome ?: '—')
                            : ' · In Company'),
                ]);

                $detalhesAtual = TreinamentoNrDetalhes::firstOrNew([
                    'tarefa_id' => $tarefa->id,
                ]);
                $detalhesAtual->local_tipo = $data['local_tipo'];
                $detalhesAtual->unidade_id = $data['local_tipo'] === 'clinica'
                    ? ($data['unidade_id'] ?? null)
                    : null;
                $detalhesAtual->treinamentos = $treinamentosPayload;
                $detalhesAtual->save();

                TreinamentoNR::where('tarefa_id', $tarefa->id)->delete();
                TreinamentoNR::create([
                    'tarefa_id' => $tarefa->id,
                    'funcionario_id' => $primeiroFuncionarioId,
                ]);

                foreach ($funcionariosIds as $funcionarioId) {
                    $inicioPrevisto = $tarefa->inicio_previsto ?? now();
                    $fimPrevisto = app(TempoTarefaService::class)
                        ->calcularFimPrevisto($inicioPrevisto, $tarefa->empresa_id, $tarefa->servico_id);
                    $nome = $funcionariosMap->get($funcionarioId)?->nome;

                    $novaTarefa = Tarefa::create([
                        'empresa_id' => $tarefa->empresa_id,
                        'cliente_id' => $tarefa->cliente_id,
                        'responsavel_id' => $tarefa->responsavel_id,
                        'coluna_id' => $tarefa->coluna_id,
                        'servico_id' => $tarefa->servico_id,
                        'funcionario_id' => $funcionarioId ?: null,
                        'titulo' => $nome ? "Treinamento NR - {$nome}" : ($tarefa->titulo ?: 'Treinamento NR'),
                        'descricao' => "Treinamento NR · Local: {$data['local_tipo']}" .
                            ($data['local_tipo'] === 'clinica'
                                ? " · Unidade: " . ($unidadeNome ?: '—')
                                : ' · In Company'),
                        'inicio_previsto' => $inicioPrevisto,
                        'fim_previsto' => $fimPrevisto,
                    ]);

                    TreinamentoNR::create([
                        'tarefa_id' => $novaTarefa->id,
                        'funcionario_id' => $funcionarioId,
                    ]);

                    TreinamentoNrDetalhes::create([
                        'tarefa_id' => $novaTarefa->id,
                        'local_tipo' => $data['local_tipo'],
                        'unidade_id' => $data['local_tipo'] === 'clinica'
                            ? ($data['unidade_id'] ?? null)
                            : null,
                        'treinamentos' => $treinamentosPayload,
                    ]);

                    TarefaLog::create([
                        'tarefa_id' => $novaTarefa->id,
                        'user_id' => $usuario->id,
                        'de_coluna_id' => null,
                        'para_coluna_id' => $novaTarefa->coluna_id,
                        'acao' => 'criado',
                        'observacao' => 'Treinamento NRs criado por desmembramento de pacote com múltiplos participantes.',
                    ]);
                }

                TarefaLog::create([
                    'tarefa_id'      => $tarefa->id,
                    'user_id'        => $usuario->id,
                    'de_coluna_id'   => $tarefa->coluna_id,
                    'para_coluna_id' => $tarefa->coluna_id,
                    'acao'           => 'atualizado',
                    'observacao'     => 'Treinamento NRs desmembrado em tarefas por participante (modo pacote).',
                ]);

                return;
            }

            // Atualiza / cria detalhes de local
            $detalhes = TreinamentoNrDetalhes::firstOrNew([
                'tarefa_id' => $tarefa->id,
            ]);

            $detalhes->local_tipo = $data['local_tipo'];
            $detalhes->unidade_id = $data['local_tipo'] === 'clinica'
                ? ($data['unidade_id'] ?? null)
                : null;
            $detalhes->treinamentos = $treinamentosPayload;
            $detalhes->save();

            // Atualiza descrição da tarefa (opcional, mas útil)
            $tarefa->update([
                'descricao' => "Treinamento NR · Local: {$data['local_tipo']}" .
                    ($data['local_tipo'] === 'clinica'
                        ? " · Unidade: " . ($unidadeNome ?: '—')
                        : ' · In Company'),
            ]);

            // Atualiza participantes (remove todos e cria de novo)
            TreinamentoNR::where('tarefa_id', $tarefa->id)->delete();

            foreach ($funcionariosIds as $funcionarioId) {
                TreinamentoNR::create([
                    'tarefa_id'      => $tarefa->id,
                    'funcionario_id' => $funcionarioId,
                ]);
            }

            // Log
            TarefaLog::create([
                'tarefa_id'      => $tarefa->id,
                'user_id'        => $usuario->id,
                'de_coluna_id'   => $tarefa->coluna_id,
                'para_coluna_id' => $tarefa->coluna_id,
                'acao'           => 'atualizado',
                'observacao'     => 'Treinamento NRs atualizado pelo usuário.',
            ]);
        });

        $origem = $request->query('origem', $request->input('origem'));
        if ($origem === 'cliente' || (method_exists($usuario, 'isCliente') && $usuario->isCliente())) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'Treinamento de NRs atualizado com sucesso.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Treinamento de NRs atualizado com sucesso.');
    }

    private function getTreinamentosDisponiveis(int $empresaId, ?Cliente $cliente = null)
    {
        $servicoTreinamentoId = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'Treinamentos NRs')
            ->value('id');

        if (!$servicoTreinamentoId) {
            return collect();
        }

        $padrao = TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->first();

        if (!$padrao) {
            return collect();
        }

        $treinamentos = TabelaPrecoItem::query()
            ->where('tabela_preco_padrao_id', $padrao->id)
            ->where('servico_id', $servicoTreinamentoId)
            ->where('ativo', true)
            ->whereNotNull('codigo')
            ->orderBy('codigo')
            ->get(['codigo', 'descricao']);

        if (!$cliente) {
            return $treinamentos;
        }

        $contrato = \App\Models\ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->where('status', 'ATIVO')
            ->latest('id')
            ->first();

        if (!$contrato) {
            return collect();
        }

        $contrato->loadMissing('parametroOrigem.itens');

        $itensOrigem = $contrato->parametroOrigem?->itens ?? collect();
        if ($itensOrigem->isEmpty()) {
            return collect();
        }

        $codigosContratados = $itensOrigem
            ->filter(function ($it) {
                $tipo = strtoupper((string) ($it->tipo ?? ''));
                return $tipo === 'TREINAMENTO_NR';
            })
            ->flatMap(function ($it) {
                $codigo = $it->meta['codigo'] ?? null;
                if (!$codigo) {
                    $nome = (string) ($it->nome ?? '');
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

        if (empty($codigosContratados)) {
            return collect();
        }

        $codigosContratados = $this->normalizeTreinamentosCodigos($codigosContratados);

        return $treinamentos
            ->filter(function ($treinamento) use ($codigosContratados) {
                $codigo = strtoupper(trim((string) $treinamento->codigo));
                return $codigo !== '' && in_array($codigo, $codigosContratados, true);
            })
            ->values();
    }

    private function getPacotesTreinamentos(int $empresaId, Cliente $cliente, ?\App\Models\ClienteContrato $contratoAtivo): array
    {
        if (!$contratoAtivo) {
            return [];
        }

        $servicoTreinamentoId = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'Treinamentos NRs')
            ->value('id');

        if (!$servicoTreinamentoId) {
            return [];
        }

        $contratoAtivo->loadMissing('itens', 'parametroOrigem.itens');
        $itensOrigem = $contratoAtivo->parametroOrigem?->itens ?? collect();
        if ($itensOrigem->isEmpty()) {
            return [];
        }

        $pacotes = [];
        $pacotesOrigem = $itensOrigem
            ->filter(fn ($it) => strtoupper((string) ($it->tipo ?? '')) === 'PACOTE_TREINAMENTOS')
            ->values();

        foreach ($pacotesOrigem as $item) {
            $descricao = trim((string) ($item->descricao ?? $item->nome ?? ''));
            $treinamentosMeta = (array) ($item->meta['treinamentos'] ?? []);
            $codigos = collect($treinamentosMeta)
                ->map(fn ($trein) => $trein['codigo'] ?? null)
                ->filter()
                ->values()
                ->all();
            $codigos = $this->normalizeTreinamentosCodigos($codigos);

            $contratoItem = $contratoAtivo->itens
                ->first(function ($it) use ($servicoTreinamentoId, $descricao) {
                    return (int) $it->servico_id === (int) $servicoTreinamentoId
                        && trim((string) ($it->descricao_snapshot ?? '')) === $descricao;
                });

            if (!$contratoItem) {
                $contratoItem = $contratoAtivo->itens
                    ->first(fn ($it) => (int) $it->servico_id === (int) $servicoTreinamentoId);
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

    private function buildTreinamentosPayload(array $data, array $pacotesTreinamentos): array
    {
        $modo = $data['treinamento_modo'] ?? 'avulso';
        if ($modo === 'pacote') {
            $pacoteId = (int) ($data['pacote_id'] ?? 0);
            $pacote = collect($pacotesTreinamentos)->firstWhere('contrato_item_id', $pacoteId);

            return [
                'modo' => 'pacote',
                'pacote' => [
                    'contrato_item_id' => $pacote['contrato_item_id'] ?? null,
                    'nome' => $pacote['nome'] ?? 'Pacote de Treinamentos',
                    'descricao' => $pacote['descricao'] ?? null,
                    'valor' => $pacote['valor'] ?? 0,
                    'codigos' => $pacote['codigos'] ?? [],
                ],
            ];
        }

        $codigos = $this->normalizeTreinamentosCodigos($data['treinamentos'] ?? []);

        return [
            'modo' => 'avulso',
            'codigos' => $codigos,
        ];
    }

    private function normalizeTreinamentosCodigos(array $codigos): array
    {
        $normalized = [];
        foreach ($codigos as $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if (preg_match('/^nr[_-]?(\\d+)$/i', $value, $m)) {
                $numero = str_pad($m[1], 2, '0', STR_PAD_LEFT);
                $normalized[] = 'NR-' . $numero;
                continue;
            }

            $normalized[] = strtoupper($value);
        }

        return array_values(array_unique($normalized));
    }

    private function contratoAtivo(Cliente $cliente): ?\App\Models\ClienteContrato
    {
        $hoje = now()->toDateString();

        return \App\Models\ClienteContrato::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->where('status', 'ATIVO')
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_inicio')->orWhereDate('vigencia_inicio', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_fim')->orWhereDate('vigencia_fim', '>=', $hoje);
            })
            ->first();
    }

    private function getTreinamentosFinalizados(int $empresaId, Cliente $cliente, ?\App\Models\ClienteContrato $contratoAtivo): array
    {
        if (!$contratoAtivo) {
            return [];
        }

        $servicoTreinamentoId = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'Treinamentos NRs')
            ->value('id');

        if (!$servicoTreinamentoId) {
            return [];
        }

        $query = Tarefa::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->where('servico_id', $servicoTreinamentoId)
            ->whereNotNull('finalizado_em');

        if (!empty($contratoAtivo->vigencia_inicio)) {
            $query->whereDate('finalizado_em', '>=', $contratoAtivo->vigencia_inicio);
        } elseif (!empty($contratoAtivo->created_at)) {
            $query->whereDate('finalizado_em', '>=', $contratoAtivo->created_at);
        }

        $tarefaIds = $query->pluck('id')->all();
        if (empty($tarefaIds)) {
            return [];
        }

        return TreinamentoNrDetalhes::query()
            ->whereIn('tarefa_id', $tarefaIds)
            ->get(['treinamentos'])
            ->flatMap(function ($row) {
                $payload = $row->treinamentos ?? [];
                if (is_array($payload) && isset($payload['modo'])) {
                    if ($payload['modo'] === 'pacote') {
                        return (array) ($payload['pacote']['codigos'] ?? []);
                    }
                    return (array) ($payload['codigos'] ?? []);
                }
                return (array) $payload;
            })
            ->map(fn ($codigo) => strtoupper(trim((string) $codigo)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
