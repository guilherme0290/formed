<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\KanbanColuna;
use App\Models\Tarefa;
use App\Models\Servico;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PainelController extends Controller
{
    // ==========================
    // PAINEL / KANBAN
    // ==========================
    public function index(Request $r)
    {
        $user    = Auth::user();
        $empresa = $user->empresa_id;

        $filtroServico     = $r->get('servico_id');
        $filtroResponsavel = $r->get('responsavel_id');
        $filtroStatus      = $r->get('status');

        // Colunas do kanban
        $colunas = KanbanColuna::where('empresa_id', $empresa)
            ->orderBy('ordem')
            ->get();

        // Query base das tarefas
        $tarefasQuery = Tarefa::with(['responsavel', 'coluna', 'cliente', 'servico'])
            ->where('empresa_id', $empresa);

        // se não for master, vê só as tarefas dele
        if (! $user->hasRole('master')) {
            $tarefasQuery->where('responsavel_id', $user->id);
        }

        if ($filtroResponsavel) {
            $tarefasQuery->where('responsavel_id', $filtroResponsavel);
        }

        // filtro por status usando coluna_id
        if ($filtroStatus) {
            $tarefasQuery->where('coluna_id', $filtroStatus);
        }

        $tarefas = $tarefasQuery->get();

        // Agrupa por coluna
        $tarefasPorColuna = $tarefas->groupBy('coluna_id');

        // Stats do topo (chaves pelo id da coluna)
        $stats = [];
        foreach ($colunas as $coluna) {
            $stats[$coluna->id] = $tarefasPorColuna->get($coluna->id, collect())->count();
        }

        // Responsáveis
        $responsaveis = Tarefa::where('empresa_id', $empresa)
            ->whereNotNull('responsavel_id')
            ->with('responsavel')
            ->get()
            ->pluck('responsavel')
            ->unique('id')
            ->filter();

        // Serviços e clientes (para filtros e modal)
        $servicos = Servico::orderBy('nome')->get();
        $clientes = Cliente::where('empresa_id', $empresa)
            ->orderBy('nome_fantasia')
            ->get();

        return view('operacional.kanban.index', [
            'usuario'           => $user,
            'colunas'           => $colunas,
            'tarefasPorColuna'  => $tarefasPorColuna,
            'stats'             => $stats,
            'responsaveis'      => $responsaveis,
            'servicos'          => $servicos,
            'clientes'          => $clientes,
            'filtroServico'     => $filtroServico,
            'filtroResponsavel' => $filtroResponsavel,
            'filtroStatus'      => $filtroStatus,
        ]);
    }

    // ==========================
    // MOVER CARD NO KANBAN
    // ==========================
    public function mover(Request $r, Tarefa $tarefa)
    {
        $user = Auth::user();

        $data = $r->validate([
            'coluna_id' => ['required', 'exists:kanban_colunas,id'],
        ]);

        $deColuna   = $tarefa->coluna_id;
        $paraColuna = $data['coluna_id'];

        $tarefa->coluna_id = $paraColuna;

        $coluna = KanbanColuna::findOrFail($paraColuna);
        // se não tiver slug, cai no "Ativa"
        $tarefa->status = $coluna->slug ?: 'Ativa';

        if ($coluna->finaliza && ! $tarefa->finalizado_em) {
            $tarefa->finalizado_em = now();
        }

        $tarefa->save();

        // ⚠️ logs desativados por enquanto, pois não existe tabela tarefas_logs
        /*
        TarefaLog::create([
            'tarefa_id'      => $tarefa->id,
            'user_id'        => $user->id ?? null,
            'de_coluna_id'   => $deColuna,
            'para_coluna_id' => $paraColuna,
            'acao'           => 'movida',
            'observacao'     => null,
        ]);
        */

        return response()->json([
            'ok'     => true,
            'status' => $tarefa->status,
        ]);
    }

    // ==========================
    // FORM LOJA - CLIENTE EXISTENTE
    // ==========================
    public function storeLojaExistente(Request $r)
    {
        $user    = Auth::user();
        $empresa = $user->empresa_id;

        $dados = $r->validate([
            'cliente_id'     => ['required', 'exists:clientes,id'],
            'unidade_id'     => ['nullable'],
            'servico_id'     => ['nullable'],
            'data'           => ['required', 'date'],
            'hora'           => ['required'],
            'prioridade'     => ['required', 'in:baixa,media,alta,Normal'],
            'prazo_sla'      => ['nullable', 'date'],
            'observacoes'    => ['nullable', 'string'],
            'status_inicial' => ['required', 'integer', 'exists:kanban_colunas,id'],
        ]);

        $coluna = KanbanColuna::where('empresa_id', $empresa)
            ->where('id', $dados['status_inicial'])
            ->firstOrFail();

        $inicioPrevisto = $dados['data'].' '.$dados['hora'].':00';
        $fimPrevisto    = $dados['prazo_sla']
            ? $dados['prazo_sla'].' 23:59:59'
            : null;

        $tituloBase = 'Tarefa Loja - Cliente Existente';
        if (!empty($dados['servico_id'])) {
            $serv = Servico::find($dados['servico_id']);
            if ($serv) {
                $tituloBase = $serv->nome.' - Loja';
            }
        }

        $tarefa = Tarefa::create([
            'empresa_id'      => $empresa,
            'coluna_id'       => $coluna->id,
            'responsavel_id'  => $user->id,
            'cliente_id'      => $dados['cliente_id'],   // grava só o cliente
            'titulo'          => $tituloBase,
            'descricao'       => $dados['observacoes'] ?? '',
            'prioridade'      => $dados['prioridade'] ?: 'Normal',
            'status'          => $coluna->nome,
            'inicio_previsto' => $inicioPrevisto,
            'fim_previsto'    => $fimPrevisto,
        ]);

        // ⚠️ sem logs por enquanto (tabela tarefas_logs não existe)
        /*
        TarefaLog::create([
            'tarefa_id'      => $tarefa->id,
            'user_id'        => $user->id,
            'de_coluna_id'   => null,
            'para_coluna_id' => $coluna->id,
            'acao'           => 'criada',
            'observacao'     => 'Tarefa criada via painel operacional (cliente existente).',
        ]);
        */

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa criada com sucesso!');
    }

    // ==========================
    // FORM LOJA - CLIENTE NOVO
    // ==========================
    public function storeLojaNovo(Request $r)
    {
        $user    = Auth::user();
        $empresa = $user->empresa_id;

        $dados = $r->validate([
            'razao_social'   => ['required', 'string', 'max:255'],
            'nome_fantasia'  => ['nullable', 'string', 'max:255'],
            'cnpj'           => ['nullable', 'string', 'max:30'],
            'telefone'       => ['nullable', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:255'],
            'unidade_id'     => ['nullable'],
            'servico_id'     => ['nullable'],
            'data'           => ['required', 'date'],
            'hora'           => ['required'],
            'prioridade'     => ['required', 'in:baixa,media,alta,Normal'],
            'prazo_sla'      => ['nullable', 'date'],
            'observacoes'    => ['nullable', 'string'],
            'status_inicial' => ['required', 'integer', 'exists:kanban_colunas,id'],
        ]);

        DB::beginTransaction();

        try {
            $cliente = Cliente::create([
                'empresa_id'    => $empresa,
                'razao_social'  => $dados['razao_social'],
                'nome_fantasia' => $dados['nome_fantasia'] ?? $dados['razao_social'],
                'cnpj'          => $dados['cnpj'] ?? null,
                'email'         => $dados['email'] ?? null,
                'telefone'      => $dados['telefone'] ?? null,
                'ativo'         => 1,
            ]);

            $coluna = KanbanColuna::where('empresa_id', $empresa)
                ->where('id', $dados['status_inicial'])
                ->firstOrFail();

            $inicioPrevisto = $dados['data'].' '.$dados['hora'].':00';
            $fimPrevisto    = $dados['prazo_sla']
                ? $dados['prazo_sla'].' 23:59:59'
                : null;

            $tituloBase = 'Tarefa Loja - Cliente Novo';
            if (!empty($dados['servico_id'])) {
                $serv = Servico::find($dados['servico_id']);
                if ($serv) {
                    $tituloBase = $serv->nome.' - Loja (novo cliente)';
                }
            }

            $tarefa = Tarefa::create([
                'empresa_id'      => $empresa,
                'coluna_id'       => $coluna->id,
                'responsavel_id'  => $user->id,
                'cliente_id'      => $cliente->id,   // grava o novo cliente
                'titulo'          => $tituloBase,
                'descricao'       => $dados['observacoes'] ?? '',
                'prioridade'      => $dados['prioridade'] ?: 'Normal',
                'status'          => $coluna->nome,
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto'    => $fimPrevisto,
            ]);

            // ⚠️ logs desativados
            /*
            TarefaLog::create([
                'tarefa_id'      => $tarefa->id,
                'user_id'        => $user->id,
                'de_coluna_id'   => null,
                'para_coluna_id' => $coluna->id,
                'acao'           => 'criada',
                'observacao'     => 'Tarefa criada via painel operacional (cliente novo).',
            ]);
            */

            DB::commit();

            return redirect()
                ->route('operacional.kanban')
                ->with('ok', 'Cliente e tarefa criados com sucesso!');

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('erro', 'Erro ao criar tarefa: '.$e->getMessage());
        }
    }
}
