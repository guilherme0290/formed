<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\KanbanColuna;
use App\Models\PgrSolicitacoes;
use App\Models\Tarefa;
use App\Models\Servico;
use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\TarefaLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Psy\Util\Str;

class PainelController extends Controller
{
    // ==========================
    // PAINEL / KANBAN
    // ==========================
    public function index(Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        // Filtros
        $filtroServico     = $request->input('servico_id');
        $filtroResponsavel = $request->input('responsavel_id');
        $filtroColuna      = $request->input('coluna_id');
        $filtroDe          = $request->input('de');
        $filtroAte         = $request->input('ate');

        // Colunas do Kanban
        $colunas = KanbanColuna::where('empresa_id', $empresaId)
            ->orderBy('ordem')
            ->get();

        // Query base das tarefas
        $tarefasQuery = Tarefa::with([
            'cliente',
            'servico',
            'responsavel',
            'coluna',
            'funcionario',
            'pgr',
            'logs.deColuna',
            'logs.paraColuna',
            'logs.user',
        ])
            ->where('empresa_id', $empresaId);

        // Aplica filtros
        if ($filtroServico) {
            $tarefasQuery->where('servico_id', $filtroServico);
        }

        if ($filtroResponsavel) {
            $tarefasQuery->where('responsavel_id', $filtroResponsavel);
        }

        if ($filtroColuna) {
            $tarefasQuery->where('coluna_id', $filtroColuna);
        }

        if ($filtroDe) {
            $tarefasQuery->whereDate('inicio_previsto', '>=', $filtroDe);
        }

        if ($filtroAte) {
            $tarefasQuery->whereDate('inicio_previsto', '<=', $filtroAte);
        }

        $tarefas = $tarefasQuery->get();

        // Agrupa tarefas por coluna
        $tarefasPorColuna = $tarefas->groupBy('coluna_id');

        // Stats por slug de coluna (para os cards do topo)
        $stats = [];
        foreach ($colunas as $coluna) {
            $colecaoColuna = $tarefasPorColuna->get($coluna->id); // pode ser null
            $stats[$coluna->slug] = $colecaoColuna ? $colecaoColuna->count() : 0;
        }

        // Listas para filtros
        $servicos = Servico::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        $responsaveis = User::where('empresa_id', $empresaId)
            ->orderBy('name')
            ->get();

        return view('operacional.kanban.index', [
            'usuario'          => $usuario,
            'colunas'          => $colunas,
            'tarefasPorColuna' => $tarefasPorColuna,
            'stats'            => $stats,

            // filtros atuais (pra dar @selected e preencher inputs)
            'servicos'         => $servicos,
            'responsaveis'     => $responsaveis,
            'filtroServico'    => $filtroServico,
            'filtroResponsavel'=> $filtroResponsavel,
            'filtroColuna'     => $filtroColuna,
            'filtroDe'         => $filtroDe,
            'filtroAte'        => $filtroAte,
        ]);
    }

    // ==========================
    // MOVER CARD NO KANBAN
    // ==========================
    public function mover(Request $request, Tarefa $tarefa)
    {
        $data = $request->validate([
            'coluna_id' => ['required', 'exists:kanban_colunas,id'],
        ]);

        $novaColunaId  = (int) $data['coluna_id'];
        $colunaAtualId = (int) $tarefa->coluna_id;

        if ($novaColunaId === $colunaAtualId) {
            return response()->json(['ok' => true]);
        }

        // Atualiza coluna
        $tarefa->update([
            'coluna_id' => $novaColunaId,
        ]);

        // Recarrega coluna para pegar o nome
        $tarefa->load('coluna');

        // Cria log de movimentação
        $log = TarefaLog::create([
            'tarefa_id'      => $tarefa->id,
            'user_id'        => Auth::id(),
            'de_coluna_id'   => $colunaAtualId,
            'para_coluna_id' => $novaColunaId,
            'acao'           => 'movido',
            'observacao'     => null,
        ]);

        $log->load(['deColuna', 'paraColuna', 'user']);

        return response()->json([
            'ok'           => true,
            'status_label' => $tarefa->coluna->nome ?? '',
            'log'          => [
                'de'   => optional($log->deColuna)->nome ?? 'Início',
                'para' => optional($log->paraColuna)->nome ?? '-',
                'user' => optional($log->user)->name ?? 'Sistema',
                'data' => optional($log->created_at)->format('d/m H:i'),
            ],
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


    public function asoSelecionarCliente(Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;
        $q         = $request->query('q');

        $clientes = Cliente::where('empresa_id', $empresaId)
            ->when($q, fn($query) =>
            $query->where('razao_social', 'like', "%{$q}%")
                ->orWhere('nome_fantasia', 'like', "%{$q}%")
            )
            ->orderBy('razao_social')
            ->paginate(12);

        return view('operacional.kanban.clientes', compact('clientes', 'q'));
    }

    public function asoSelecionarServico(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        // Por enquanto só ASO estará clicável
        return view('operacional.kanban.servicos', compact('cliente'));
    }

    public function asoCreate(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $funcionarios = Funcionario::where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->orderBy('nome')
            ->get();

        // Se ainda não tiver tabela de unidades, você pode deixar um array fixo por enquanto
        $unidades = \App\Models\UnidadeClinica::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        $tiposAso = [
            'admissional'      => 'Admissional',
            'periodico'        => 'Periódico',
            'demissional'      => 'Demissional',
            'mudanca_funcao'   => 'Mudança de Função',
            'retorno_trabalho' => 'Retorno ao Trabalho',
        ];


        $treinamentosDisponiveis = [
            'nr_35' => 'NR-35 - Trabalho em Altura',
            'nr_18' => 'NR-18 - Integração',
            'nr_12' => 'NR-12 - Máquinas e Equipamentos',
            'nr_06' => 'NR-06 - EPI',
            'nr_05' => 'NR-05 - CIPA Designada',
            'nr_01' => 'NR-01 - Ordem de Serviço',
            'nr_33' => 'NR-33 - Espaço Confinado',
            'nr_11' => 'NR-11 - Movimentação de Carga',
            'nr_10' => 'NR-10 - Elétrica'
        ];

        return view('operacional.kanban.aso.create', [
            'cliente'                => $cliente,
            'funcionarios'           => $funcionarios,
            'unidades'               => $unidades,
            'tiposAso'               => $tiposAso,
            'treinamentosDisponiveis'=> $treinamentosDisponiveis,
        ]);
    }

    public function asoStore(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'funcionario_id'        => ['nullable', 'exists:funcionarios,id'],
            'nome'                  => ['nullable', 'string', 'max:255'],
            'cpf'                   => ['nullable', 'string', 'max:20'],
            'data_nascimento'       => ['nullable', 'date'],
            'rg'                    => ['nullable', 'string', 'max:255'],
            'funcao'                 => ['nullable', 'string', 'max:255'],
            'tipo_aso'              => ['required', 'in:admissional,periodico,demissional,mudanca_funcao,retorno_trabalho'],
            'data_aso'              => ['required', 'date_format:Y-m-d'],
            'unidade_id'            => ['required', 'exists:unidades_clinicas,id'],
            'vai_fazer_treinamento' => ['nullable', 'boolean'],
            'treinamentos'          => ['array'],
            'treinamentos.*'        => ['string'],
        ]);

        $tarefa = DB::transaction(function () use ($data, $empresaId, $cliente, $usuario) {

            // 1) Resolve funcionário (existente ou novo)
            if (!empty($data['funcionario_id'])) {
                $funcionario = Funcionario::where('empresa_id', $empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->findOrFail($data['funcionario_id']);
            } else {
                $funcionario = Funcionario::create([
                    'empresa_id'    => $empresaId,
                    'cliente_id'    => $cliente->id,
                    'nome'          => $data['nome'],
                    'cpf'           => $data['cpf'] ?? null,
                    'rg' => $data['rg'],
                    'data_nascimento' =>$data['data_nascimento'],
                    'funcao' => $data['funcao']
                    ]);
            }

            // 2) Coluna inicial do Kanban (Pendente)
            $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
                ->where('slug', 'pendente') // se tiver slug
                ->first()
                ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

            $servicoAsoId = Servico::where('nome', 'ASO')->value('id');

            // 3) Monta título e descrição da tarefa
            $tipoAsoLabel = match ($data['tipo_aso']) {
                'admissional'      => 'Admissional',
                'periodico'        => 'Periódico',
                'demissional'      => 'Demissional',
                'mudanca_funcao'   => 'Mudança de Função',
                'retorno_trabalho' => 'Retorno ao Trabalho',
            };

            $titulo = "ASO - {$funcionario->nome}";
            $descricao = "Tipo: {$tipoAsoLabel}";

            if (!empty($data['vai_fazer_treinamento']) && !empty($data['treinamentos'])) {
                $descricao .= ' | Treinamentos: ' . implode(', ', $data['treinamentos']);
            }

            // 4) Cria a tarefa
            $tarefa = Tarefa::create([
                'empresa_id'     => $empresaId,
                'coluna_id'      => optional($colunaInicial)->id,
                'cliente_id'     => $cliente->id,
                'responsavel_id' => $usuario->id,
                'funcionario_id' => $funcionario->id,
                'servico_id'     => $servicoAsoId,
                'titulo'         => $titulo,
                'descricao'      => $descricao,
                'inicio_previsto'=> $data['data_aso'], // usar campo já existente
                // se você tiver campos específicos de ASO, pode setar aqui
            ]);

            // Opcional: primeiro log "Criada"
            TarefaLog::create([
                'tarefa_id'     => $tarefa->id,
                'user_id'       => $usuario->id,
                'de_coluna_id'  => null,
                'acao' => 'criado',
                'para_coluna_id'=> optional($colunaInicial)->id,
                'observacao'     => 'Tarefa ASO criada pelo usuário.',
            ]);

            return $tarefa;
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', "Tarefa ASO criada para o colaborador {$tarefa->titulo}.");
    }


}
