<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\KanbanColuna;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Models\Servico;
use App\Models\Cliente;          // ✅ importa Cliente
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PainelController extends Controller
{
    public function index(Request $r)
    {
        $user    = Auth::user();
        $empresa = $user->empresa_id;

        $filtroServico     = $r->get('servico_id');
        $filtroResponsavel = $r->get('responsavel_id');
        $filtroStatus      = $r->get('status'); // slug da coluna

        // Colunas do kanban da empresa
        $colunas = KanbanColuna::where('empresa_id', $empresa)
            ->orderBy('ordem')
            ->get();

        // Query base das tarefas
        $tarefasQuery = Tarefa::with(['cliente','servico','responsavel','coluna'])
            ->where('empresa_id', $empresa);

        // se não for MASTER, vê só as suas tarefas
        if (! $user->hasRole('master')) {
            $tarefasQuery->where('responsavel_id', $user->id);
        }

        if ($filtroServico) {
            $tarefasQuery->where('servico_id', $filtroServico);
        }

        if ($filtroResponsavel) {
            $tarefasQuery->where('responsavel_id', $filtroResponsavel);
        }

        if ($filtroStatus) {
            $tarefasQuery->whereHas('coluna', function ($q) use ($filtroStatus) {
                $q->where('slug', $filtroStatus);
            });
        }

        $tarefas = $tarefasQuery->get();

        // Agrupa tarefas por coluna para montar o board
        $tarefasPorColuna = $tarefas->groupBy('coluna_id');

        // Estatísticas do topo
        $stats = [];
        foreach ($colunas as $coluna) {
            $stats[$coluna->slug] = $tarefasPorColuna->get($coluna->id, collect())->count();
        }

        // Responsáveis
        $responsaveis = Tarefa::where('empresa_id', $empresa)
            ->whereNotNull('responsavel_id')
            ->with('responsavel')
            ->get()
            ->pluck('responsavel')
            ->unique('id')
            ->filter();

        // Serviços
        $servicos = Servico::orderBy('nome')->get();

        // ✅ Clientes (pro passo 1 do modal)
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
            'clientes'          => $clientes,     // ✅ manda pra view
            'filtroServico'     => $filtroServico,
            'filtroResponsavel' => $filtroResponsavel,
            'filtroStatus'      => $filtroStatus,
        ]);
    }

    public function mover(Request $r, Tarefa $tarefa)
    {
        $user = Auth::user();

        $data = $r->validate([
            'coluna_id' => ['required','exists:kanban_colunas,id'],
        ]);

        $deColuna   = $tarefa->coluna_id;
        $paraColuna = $data['coluna_id'];

        // atualiza tarefa
        $tarefa->coluna_id = $paraColuna;

        // espelha status pela coluna
        $coluna = KanbanColuna::findOrFail($paraColuna);
        $tarefa->status = $coluna->slug;

        if ($coluna->finaliza && ! $tarefa->concluida_em) {
            $tarefa->concluida_em = now();
        }

        $tarefa->save();

        // registra log
        TarefaLog::create([
            'tarefa_id'     => $tarefa->id,
            'user_id'       => $user->id ?? null,
            'de_coluna_id'  => $deColuna,
            'para_coluna_id'=> $paraColuna,
            'acao'          => 'movida',
            'observacao'    => null,
        ]);

        return response()->json([
            'ok'     => true,
            'status' => $tarefa->status,
        ]);
    }
}
