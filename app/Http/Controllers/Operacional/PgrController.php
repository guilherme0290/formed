<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Funcao;
use App\Models\KanbanColuna;
use App\Models\PgrSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PgrController extends Controller
{
    public function pgrTipo(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $origem = $request->query('origem'); // 'cliente' ou null

        return view('operacional.kanban.pgr.tipo', [
            'cliente' => $cliente,
            'origem'  => $origem,
        ]);
    }

    /**
     * Passo 2 – formulário PGR (Matriz ou Específico)
     * query param: ?tipo=matriz ou ?tipo=especifico
     */
    public function pgrCreate(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $tipo   = $request->query('tipo', 'matriz');
        $origem = $request->query('origem'); // 'cliente' ou null

        $funcoes = Funcao::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        if (!in_array($tipo, ['matriz', 'especifico'], true)) {
            abort(404);
        }

        $tipoLabel = $tipo === 'matriz' ? 'Matriz' : 'Específico';
        $valorArt  = 500.00;

        return view('operacional.kanban.pgr.form', [
            'cliente'   => $cliente,
            'tipo'      => $tipo,
            'funcoes'   => $funcoes,
            'tipoLabel' => $tipoLabel,
            'valorArt'  => $valorArt,
            'tarefa'    => null,
            'pgr'       => null,
            'modo'      => 'create',
            'origem'    => $origem,
        ]);
    }

    public function pgrEdit(Tarefa $tarefa, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        // relação na Tarefa: public function pgr() { return $this->hasOne(PgrSolicitacoes::class, 'tarefa_id'); }
        $pgr = $tarefa->pgr;
        abort_if(!$pgr, 404);

        $cliente = $tarefa->cliente;

        $tipo      = $pgr->tipo; // matriz|especifico
        $tipoLabel = $tipo === 'matriz' ? 'Matriz' : 'Específico';

        $funcoes = Funcao::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        // se quiser manter o valor já salvo, senão usa fixo
        $valorArt = $pgr->valor_art ?? 500.00;

        return view('operacional.kanban.pgr.form', [
            'cliente'   => $cliente,
            'tipo'      => $tipo,
            'tipoLabel' => $tipoLabel,
            'funcoes'   => $funcoes,
            'valorArt'  => $valorArt,

            // extras para modo edição
            'tarefa'    => $tarefa,
            'pgr'       => $pgr,
            'modo'      => 'edit',
        ]);
    }

    public function pgrUpdate(Tarefa $tarefa, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $pgr = $tarefa->pgr;
        abort_if(!$pgr, 404);

        // mesma validação do store
        $data = $request->validate([
            'tipo'         => ['required', 'in:matriz,especifico'],
            'com_art'      => ['required', 'boolean'],
            'qtd_homens'   => ['required', 'integer', 'min:0'],
            'qtd_mulheres' => ['required', 'integer', 'min:0'],

            'contratante_nome'        => ['nullable', 'string', 'max:255'],
            'contratante_cnpj'        => ['nullable', 'string', 'max:20'],
            'obra_nome'               => ['nullable', 'string', 'max:255'],
            'obra_endereco'           => ['nullable', 'string'],
            'obra_cej_cno'            => ['nullable', 'string', 'max:50'],
            'obra_turno_trabalho'     => ['nullable', 'string', 'max:255'],

            'funcoes'                 => ['required', 'array', 'min:1'],
            'funcoes.*.funcao_id'     => ['required', 'integer', 'exists:funcoes,id'],
            'funcoes.*.quantidade'    => ['required', 'integer', 'min:1'],
            'funcoes.*.cbo'           => ['nullable', 'string', 'max:20'],
            'funcoes.*.descricao'     => ['nullable', 'string'],
        ]);

        $totalTrabalhadores = (int)$data['qtd_homens'] + (int)$data['qtd_mulheres'];

        $totalFuncoes = collect($data['funcoes'])
            ->sum(function ($funcao) {
                return (int)($funcao['quantidade'] ?? 0);
            });

        if ($totalFuncoes !== $totalTrabalhadores) {
            return back()
                ->withInput()
                ->withErrors([
                    'funcoes' => "A soma das quantidades das funções ({$totalFuncoes}) deve ser igual ao total de trabalhadores ({$totalTrabalhadores}).",
                ]);
        }

        $tipoLabel = $data['tipo'] === 'matriz' ? 'Matriz' : 'Específico';
        $valorArt  = $data['com_art'] ? 500.00 : null;

        DB::transaction(function () use (
            $data,
            $pgr,
            $tarefa,
            $tipoLabel,
            $totalTrabalhadores,
            $valorArt,
            $usuario
        ) {
            // Atualiza PGR
            $pgr->update([
                'tipo'                => $data['tipo'],
                'com_art'             => (bool) $data['com_art'],
                'qtd_homens'          => $data['qtd_homens'],
                'qtd_mulheres'        => $data['qtd_mulheres'],
                'total_trabalhadores' => $totalTrabalhadores,
                'funcoes'             => $data['funcoes'],
                'valor_art'           => $valorArt,

                'contratante_nome'    => $data['contratante_nome'] ?? null,
                'contratante_cnpj'    => $data['contratante_cnpj'] ?? null,
                'obra_nome'           => $data['obra_nome'] ?? null,
                'obra_endereco'       => $data['obra_endereco'] ?? null,
                'obra_cej_cno'        => $data['obra_cej_cno'] ?? null,
                'obra_turno_trabalho' => $data['obra_turno_trabalho'] ?? null,
            ]);

            // Monta descrição amigável considerando se já tem PCMSO
            $descricao = "PGR - {$tipoLabel}";
            if ($pgr->com_pcms0) {
                $descricao .= ' + PCMSO';
            }
            if ($pgr->com_art) {
                $descricao .= ' (COM ART)';
            }

            $tarefa->update([
                'titulo'    => "PGR - {$tipoLabel}",
                'descricao' => $descricao,
            ]);

            // log
            TarefaLog::create([
                'tarefa_id'      => $tarefa->id,
                'user_id'        => $usuario->id,
                'de_coluna_id'   => $tarefa->coluna_id,
                'para_coluna_id' => $tarefa->coluna_id,
                'acao'           => 'atualizado',
                'observacao'     => 'Dados do PGR atualizados.',
            ]);
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'PGR atualizado com sucesso.');
    }


    /**
     * Passo 3 – salvar PGR e criar Tarefa + PgrSolicitacao
     */
    public function pgrStore(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'tipo'         => ['required', 'in:matriz,especifico'],
            'com_art'      => ['required', 'boolean'],
            'qtd_homens'   => ['required', 'integer', 'min:0'],
            'qtd_mulheres' => ['required', 'integer', 'min:0'],

            // Campos só usados no PGR Específico – deixam como nullable
            'contratante_nome'        => ['nullable', 'string', 'max:255'],
            'contratante_cnpj'        => ['nullable', 'string', 'max:20'],
            'obra_nome'               => ['nullable', 'string', 'max:255'],
            'obra_endereco'           => ['nullable', 'string'],
            'obra_cej_cno'            => ['nullable', 'string', 'max:50'],
            'obra_turno_trabalho'     => ['nullable', 'string', 'max:255'],

            'funcoes'                 => ['required', 'array', 'min:1'],
            'funcoes.*.funcao_id'     => ['required', 'integer', 'exists:funcoes,id'],
            'funcoes.*.quantidade'    => ['required', 'integer', 'min:1'],
            'funcoes.*.cbo'           => ['nullable', 'string', 'max:20'],
            'funcoes.*.descricao'     => ['nullable', 'string'],
        ]);

        // valida soma das quantidades x total trabalhadores
        $totalTrabalhadores = (int)$data['qtd_homens'] + (int)$data['qtd_mulheres'];

        $totalFuncoes = collect($data['funcoes'])
            ->sum(function ($funcao) {
                return (int)($funcao['quantidade'] ?? 0);
            });

        if ($totalFuncoes !== $totalTrabalhadores) {
            return back()
                ->withInput()
                ->withErrors([
                    'funcoes' => "A soma das quantidades das funções ({$totalFuncoes}) deve ser igual ao total de trabalhadores ({$totalTrabalhadores}).",
                ]);
        }

        // coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // serviço PGR
        $servicoPgr = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'PGR')
            ->first();

        $tipoLabel = $data['tipo'] === 'matriz' ? 'Matriz' : 'Específico';
        $valorArt  = $data['com_art'] ? 500.00 : null; // se for fixo

        $tarefaId = null;

        DB::transaction(function () use (
            $data,
            $empresaId,
            $cliente,
            $usuario,
            $colunaInicial,
            $servicoPgr,
            $tipoLabel,
            $totalTrabalhadores,
            $valorArt,
            &$tarefaId
        ) {
            // cria Tarefa base
            $tarefa = Tarefa::create([
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'responsavel_id' => $usuario->id,
                'coluna_id'      => optional($colunaInicial)->id,
                'servico_id'     => optional($servicoPgr)->id,
                'titulo'         => "PGR - {$tipoLabel}",
                'descricao'      => "PGR - {$tipoLabel}" . ($data['com_art'] ? ' (COM ART)' : ''),
                'inicio_previsto'=> now(),
            ]);

            $tarefaId = $tarefa->id;

            // cria registro PGR (tabela pgr_solicitacoes)
            PgrSolicitacoes::create([
                'empresa_id'        => $empresaId,
                'cliente_id'        => $cliente->id,
                'tarefa_id'         => $tarefa->id,

                'tipo'              => $data['tipo'],
                'com_art'           => (bool) $data['com_art'],

                'contratante_nome'      => $data['contratante_nome'] ?? null,
                'contratante_cnpj'      => $data['contratante_cnpj'] ?? null,
                'obra_nome'             => $data['obra_nome'] ?? null,
                'obra_endereco'         => $data['obra_endereco'] ?? null,
                'obra_cej_cno'          => $data['obra_cej_cno'] ?? null,
                'obra_turno_trabalho'   => $data['obra_turno_trabalho'] ?? null,

                'qtd_homens'        => $data['qtd_homens'],
                'qtd_mulheres'      => $data['qtd_mulheres'],
                // se a coluna for JSON, no model use casts ['funcoes' => 'array']
                'funcoes'           => $data['funcoes'],

                // será definido na etapa "Precisa de PCMSO?"
                'com_pcms0'         => false,
                'valor_art'         => $valorArt,
                'total_trabalhadores' => $totalTrabalhadores,
            ]);

            // log inicial
            TarefaLog::create([
                'tarefa_id'     => $tarefa->id,
                'user_id'       => $usuario->id,
                'de_coluna_id'  => null,
                'para_coluna_id'=> optional($colunaInicial)->id,
                'acao'          => 'criado',
                'observacao'    => 'Tarefa PGR criada pelo usuário.',
            ]);
        });

        // redireciona para pergunta de PCMSO
        return redirect()
            ->route('operacional.kanban.pgr.pcmso', $tarefaId);
    }


    /**
     * Passo 4 – tela "Precisa de PCMSO?"
     */
    public function pgrPcmso(Tarefa $tarefa, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $pgr = $tarefa->pgr;
        abort_if(!$pgr, 404);

        $cliente = $tarefa->cliente ?? null;

        return view('operacional.kanban.pgr.pcmso', [
            'tarefa'  => $tarefa,
            'pgr'     => $pgr,
            'cliente' => $cliente,
        ]);
    }

    /**
     * Salvar resposta da pergunta de PCMSO (sim/não)
     */
    public function pgrPcmsoStore(Tarefa $tarefa, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'com_pcms0' => ['required', 'boolean'],
        ]);

        $pgr = $tarefa->pgr;
        abort_if(!$pgr, 404);

        $pgr->update([
            'com_pcms0' => (bool)$data['com_pcms0'],
        ]);

        // monta uma descrição mais amigável pra aparecer no modal
        $descricao = "PGR - " . ($pgr->tipo === 'matriz' ? 'Matriz' : 'Específico');

        if ($pgr->com_pcms0) {
            $descricao .= ' + PCMSO';
        }

        if ($pgr->com_art) {
            $descricao .= ' (COM ART)';
        }

        $tarefa->update([
            'descricao' => $descricao,
        ]);

        if ($usuario->isCliente()) {
            return redirect()
                ->route('cliente.dashboard')
                ->with('ok', 'Solicitação de PGR criada com sucesso e enviada para análise.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa PGR criada e enviada para a coluna Pendente.');
    }
}
