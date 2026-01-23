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
            'isEdit'       => false,
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id;

        // Validação única
        $treinamentosDisponiveis = $this->getTreinamentosDisponiveis($empresaId, $cliente);
        $treinamentosCodigos = $treinamentosDisponiveis->pluck('codigo')->filter()->values()->all();
        if (empty($treinamentosCodigos)) {
            return back()
                ->withErrors(['treinamentos' => 'Nenhum treinamento contratado para este cliente.'])
                ->withInput();
        }

        $data = $request->validate([
            'funcionarios'   => ['required', 'array', 'min:1'],
            'funcionarios.*' => ['integer', 'exists:funcionarios,id'],
            'treinamentos'   => ['required', 'array', 'min:1'],
            'treinamentos.*' => ['string', Rule::in($treinamentosCodigos)],

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
            $tipoLabel
        ) {
            // Cria a tarefa no padrão das demais
            $tarefa = Tarefa::create([
                'empresa_id'      => $empresaId,
                'cliente_id'      => $cliente->id,
                'responsavel_id'  => $usuario->id,
                'coluna_id'       => optional($colunaInicial)->id,
                'servico_id'      => optional($servicoTreinamentosNr)->id,
                'titulo'          => "Treinamento NR",
                'descricao'       => "Treinamento NR - {$tipoLabel} · Local: {$data['local_tipo']}"
                    . ($data['local_tipo'] === 'clinica'
                        ? " · Unidade ID: {$data['unidade_id']}"
                        : ' · In Company'),
                'inicio_previsto' => now(),
            ]);

            // Participantes
            foreach ($data['funcionarios'] as $funcionarioId) {
                TreinamentoNR::create([
                    'tarefa_id'      => $tarefa->id,
                    'funcionario_id' => $funcionarioId,
                ]);
            }

            // Detalhes de local/unidade
            TreinamentoNrDetalhes::create([
                'tarefa_id'  => $tarefa->id,
                'local_tipo' => $data['local_tipo'],
                'unidade_id' => $data['unidade_id'] ?? null,
                'treinamentos' => $data['treinamentos'],
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
        $treinamentosCodigos = $treinamentosDisponiveis->pluck('codigo')->filter()->values()->all();
        if (empty($treinamentosCodigos)) {
            return back()
                ->withErrors(['treinamentos' => 'Nenhum treinamento contratado para este cliente.'])
                ->withInput();
        }

        $data = $request->validate([
            'funcionarios'   => ['required', 'array', 'min:1'],
            'funcionarios.*' => ['integer', 'exists:funcionarios,id'],
            'treinamentos'   => ['required', 'array', 'min:1'],
            'treinamentos.*' => ['string', Rule::in($treinamentosCodigos)],

            'local_tipo' => ['required', 'in:clinica,empresa'],
            'unidade_id' => ['required_if:local_tipo,clinica', 'nullable', 'integer', 'exists:unidades_clinicas,id'],
        ], [
            'funcionarios.required' => 'Selecione pelo menos um participante.',
            'treinamentos.required' => 'Selecione pelo menos um treinamento.',
        ]);

        DB::transaction(function () use ($data, $tarefa, $usuario) {

            // Atualiza / cria detalhes de local
            $detalhes = TreinamentoNrDetalhes::firstOrNew([
                'tarefa_id' => $tarefa->id,
            ]);

            $detalhes->local_tipo = $data['local_tipo'];
            $detalhes->unidade_id = $data['local_tipo'] === 'clinica'
                ? ($data['unidade_id'] ?? null)
                : null;
            $detalhes->treinamentos = $data['treinamentos'];
            $detalhes->save();

            // Atualiza descrição da tarefa (opcional, mas útil)
            $tarefa->update([
                'descricao' => "Treinamento NR · Local: {$data['local_tipo']}" .
                    ($data['local_tipo'] === 'clinica'
                        ? " · Unidade ID: {$detalhes->unidade_id}"
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

        if (!$contrato || !$contrato->propostaOrigem) {
            return collect();
        }

        $contrato->loadMissing('propostaOrigem.itens');

        $codigosContratados = $contrato->propostaOrigem->itens
            ->filter(fn ($it) => strtoupper((string) $it->tipo) === 'TREINAMENTO_NR')
            ->map(function ($it) {
                $codigo = $it->meta['codigo'] ?? null;
                if (!$codigo) {
                    $nome = (string) ($it->nome ?? '');
                    if ($nome !== '' && preg_match('/^(NR[-\\s]?\\d+[A-Z]?)/i', $nome, $m)) {
                        $codigo = str_replace(' ', '-', $m[1]);
                    }
                }
                $codigo = strtoupper(trim((string) $codigo));
                return $codigo !== '' ? $codigo : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($codigosContratados)) {
            return collect();
        }

        return $treinamentos
            ->filter(function ($treinamento) use ($codigosContratados) {
                $codigo = strtoupper(trim((string) $treinamento->codigo));
                return $codigo !== '' && in_array($codigo, $codigosContratados, true);
            })
            ->values();
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
                return array_map(
                    fn ($codigo) => strtoupper(trim((string) $codigo)),
                    (array) ($row->treinamentos ?? [])
                );
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
