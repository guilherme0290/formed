<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\KanbanColuna;
use App\Models\Tarefa;
use App\Models\Servico;
use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // ===== STATS DO TOPO =====
        // chaves fixas para bater com o Blade
        $stats = [
            'pendente'           => 0,
            'em_execucao'        => 0,
            'aguardando_cliente' => 0,
            'concluido'          => 0,
            'atrasado'           => 0,
        ];

        foreach ($colunas as $coluna) {
            $qtd  = $tarefasPorColuna->get($coluna->id, collect())->count();
            $nome = mb_strtolower($coluna->nome, 'UTF-8');

            // mapeia pelo nome da coluna
            if (str_contains($nome, 'fazer') || str_contains($nome, 'pendente')) {
                $stats['pendente'] += $qtd;
            } elseif (str_contains($nome, 'andamento') || str_contains($nome, 'execução') || str_contains($nome, 'execucao')) {
                $stats['em_execucao'] += $qtd;
            } elseif (str_contains($nome, 'aprova') || str_contains($nome, 'aguardando')) {
                $stats['aguardando_cliente'] += $qtd;
            } elseif (str_contains($nome, 'conclu')) {
                $stats['concluido'] += $qtd;
            }
        }

        // atrasado = data_prevista passada e não finalizado
        // (se você não usar data_prevista, pode adaptar depois para inicio_previsto)
        if (Schema::hasColumn('tarefas', 'data_prevista')) {
            $stats['atrasado'] = Tarefa::where('empresa_id', $empresa)
                ->whereNull('finalizado_em')
                ->whereNotNull('data_prevista')
                ->whereDate('data_prevista', '<', now()->toDateString())
                ->count();
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

        return view('operacional.Kanban.index', [
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

        // status = nome da coluna
        $tarefa->status = $coluna->nome;

        if ($coluna->finaliza && ! $tarefa->finalizado_em) {
            $tarefa->finalizado_em = now();
        }

        $tarefa->save();

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

        // monta dados da tarefa
        $dadosTarefa = [
            'empresa_id'      => $empresa,
            'coluna_id'       => $coluna->id,
            'responsavel_id'  => $user->id,
            'cliente_id'      => $dados['cliente_id'],
            'servico_id'      => $dados['servico_id'] ?? null,
            'titulo'          => $tituloBase,
            'descricao'       => $dados['observacoes'] ?? '',
            'prioridade'      => $dados['prioridade'] ?: 'Normal',
            'status'          => $coluna->nome,
            'inicio_previsto' => $inicioPrevisto,   // <- aqui vai a data do exame
            'fim_previsto'    => $fimPrevisto,
        ];

        // se existir coluna data_prevista, também grava a data do exame lá
        if (Schema::hasColumn('tarefas', 'data_prevista')) {
            $dadosTarefa['data_prevista'] = $dados['data'];
        }

        // cria tarefa
        $tarefa = Tarefa::create($dadosTarefa);

        // se vier dados do funcionário, cria também
        if ($r->filled('funcionario_nome')) {
            Funcionario::create([
                'empresa_id'            => $empresa,
                'cliente_id'            => $dados['cliente_id'],
                'nome'                  => $r->input('funcionario_nome'),
                'cpf'                   => $r->input('funcionario_cpf'),
                'rg'                    => $r->input('funcionario_rg'),
                'data_nascimento'       => $r->input('funcionario_nascimento'),
                'data_admissao'         => $r->input('funcionario_admissao'),
                'funcao'                => $r->input('funcionario_funcao'),
                'treinamento_nr'        => $r->boolean('funcionario_treinamento_nr'),
                'exame_admissional'     => $r->boolean('exame_admissional'),
                'exame_periodico'       => $r->boolean('exame_periodico'),
                'exame_demissional'     => $r->boolean('exame_demissional'),
                'exame_mudanca_funcao'  => $r->boolean('exame_mudanca_funcao'),
                'exame_retorno_trabalho'=> $r->boolean('exame_retorno_trabalho'),
            ]);
        }

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
            // cria cliente
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

            // monta dados da tarefa
            $dadosTarefa = [
                'empresa_id'      => $empresa,
                'coluna_id'       => $coluna->id,
                'responsavel_id'  => $user->id,
                'cliente_id'      => $cliente->id,
                'servico_id'      => $dados['servico_id'],
                'titulo'          => $tituloBase,
                'descricao'       => $dados['observacoes'] ?? '',
                'prioridade'      => $dados['prioridade'] ?: 'Normal',
                'status'          => $coluna->nome,
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto'    => $fimPrevisto,
            ];

            if (Schema::hasColumn('tarefas', 'data_prevista')) {
                $dadosTarefa['data_prevista'] = $dados['data'];
            }

            // cria tarefa
            $tarefa = Tarefa::create($dadosTarefa);

            // cria funcionário se informado
            if ($r->filled('funcionario_nome')) {
                Funcionario::create([
                    'empresa_id'            => $empresa,
                    'cliente_id'            => $cliente->id,
                    'nome'                  => $r->input('funcionario_nome'),
                    'cpf'                   => $r->input('funcionario_cpf'),
                    'rg'                    => $r->input('funcionario_rg'),
                    'data_nascimento'       => $r->input('funcionario_nascimento'),
                    'data_admissao'         => $r->input('funcionario_admissao'),
                    'funcao'                => $r->input('funcionario_funcao'),
                    'treinamento_nr'        => $r->boolean('funcionario_treinamento_nr'),
                    'exame_admissional'     => $r->boolean('exame_admissional'),
                    'exame_periodico'       => $r->boolean('exame_periodico'),
                    'exame_demissional'     => $r->boolean('exame_demissional'),
                    'exame_mudanca_funcao'  => $r->boolean('exame_mudanca_funcao'),
                    'exame_retorno_trabalho'=> $r->boolean('exame_retorno_trabalho'),
                ]);
            }

            DB::commit();

            return redirect()
                ->route('operacional.kanban')
                ->with('ok', 'Cliente, funcionário e tarefa criados com sucesso!');

        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('erro', 'Erro ao criar tarefa: '.$e->getMessage());
        }
    }
}
