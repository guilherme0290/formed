<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
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
use Illuminate\Validation\Rule;

class TreinamentoNrController extends Controller
{
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
        $unidades = UnidadeClinica::where('empresa_id', $empresaId)->orderBy('nome')->get();

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

        // Validação única
        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId, $cliente);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente, $this->contratoAtivo($cliente));
        $pacotesIds = collect($pacotesTreinamentos)->pluck('contrato_item_id')->filter()->values()->all();
        $treinamentosCodigos = $treinamentosDisponiveis->pluck('codigo')->filter()->values()->all();
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
            'unidade_id' => ['required_if:local_tipo,clinica', 'nullable', 'integer', 'exists:unidades_clinicas,id'],
        ], [
            'funcionarios.required' => 'Selecione pelo menos um participante.',
            'treinamentos.required' => 'Selecione pelo menos um treinamento.',
        ]);

        // Coluna inicial do Kanban (igual você usa em outros serviços)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // Serviço Treinamentos NR
        $servicoTreinamentosNr = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'Treinamentos NRs')
            ->first();
        $tipoLabel             = 'Treinamentos de NRs';

        DB::transaction(function () use (
            $data,
            $cliente,
            $usuario,
            $empresaId,
            $colunaInicial,
            $servicoTreinamentosNr,
            $tipoLabel,
            $pacotesTreinamentos
        ) {
            $unidadeNome = null;
            if ($data['local_tipo'] === 'clinica' && !empty($data['unidade_id'])) {
                $unidadeNome = UnidadeClinica::query()->where('empresa_id', $empresaId)->find($data['unidade_id'])?->nome;
            }

            // Cria a tarefa no padrão das demais
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

            // Participantes
            foreach ($data['funcionarios'] as $funcionarioId) {
                TreinamentoNR::create([
                    'tarefa_id'      => $tarefa->id,
                    'funcionario_id' => $funcionarioId,
                ]);
            }

            // Detalhes de local/unidade
            $treinamentosPayload = $this->buildTreinamentosPayload($data, $pacotesTreinamentos);

            TreinamentoNrDetalhes::create([
                'tarefa_id'  => $tarefa->id,
                'local_tipo' => $data['local_tipo'],
                'unidade_id' => $data['unidade_id'] ?? null,
                'treinamentos' => $treinamentosPayload,
            ]);
        });

        if (method_exists($usuario, 'isCliente') && $usuario->isCliente()) {
            return redirect()
                ->route('cliente.dashboard')
                ->with('ok', 'Solicitação de Treinamento de NRs criada com sucesso e enviada para análise.');
        }

        return redirect()
            ->route('operacional.painel')
            ->with('ok', 'Tarefa de Treinamento de NRs criada com sucesso.');
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

    public function edit(Tarefa $tarefa)
    {
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

        $unidades = UnidadeClinica::where('empresa_id', $empresaId)->orderBy('nome')->get();

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
        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $cliente = $tarefa->cliente;

        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId, $cliente);
        $pacotesTreinamentos = $this->getPacotesTreinamentos($empresaId, $cliente, $this->contratoAtivo($cliente));
        $pacotesIds = collect($pacotesTreinamentos)->pluck('contrato_item_id')->filter()->values()->all();
        $treinamentosCodigos = $treinamentosDisponiveis->pluck('codigo')->filter()->values()->all();
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
            'unidade_id' => ['required_if:local_tipo,clinica', 'nullable', 'integer', 'exists:unidades_clinicas,id'],
        ], [
            'funcionarios.required' => 'Selecione pelo menos um participante.',
            'treinamentos.required' => 'Selecione pelo menos um treinamento.',
        ]);

        DB::transaction(function () use ($data, $tarefa, $usuario, $pacotesTreinamentos) {
            $unidadeNome = null;
            if ($data['local_tipo'] === 'clinica' && !empty($data['unidade_id'])) {
                $unidadeNome = UnidadeClinica::query()
                    ->where('empresa_id', $tarefa->empresa_id)
                    ->find($data['unidade_id'])?->nome;
            }

            // Atualiza / cria detalhes de local
            $detalhes = TreinamentoNrDetalhes::firstOrNew([
                'tarefa_id' => $tarefa->id,
            ]);

            $detalhes->local_tipo = $data['local_tipo'];
            $detalhes->unidade_id = $data['local_tipo'] === 'clinica'
                ? ($data['unidade_id'] ?? null)
                : null;
            $detalhes->treinamentos = $this->buildTreinamentosPayload($data, $pacotesTreinamentos);
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

            foreach ($data['funcionarios'] as $funcionarioId) {
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
                ->route('cliente.dashboard')
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
