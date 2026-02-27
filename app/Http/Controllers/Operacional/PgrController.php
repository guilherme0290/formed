<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Operacional\Concerns\ValidatesClientePortalTaskEditing;
use App\Http\Controllers\Controller;
use App\Models\Anexos;
use App\Models\Cliente;
use App\Models\Funcao;
use App\Models\Funcionario;
use App\Services\AsoGheService;
use App\Models\KanbanColuna;
use App\Models\PgrSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Services\ContratoClienteService;
use App\Services\TempoTarefaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PgrController extends Controller
{
    use ValidatesClientePortalTaskEditing;

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

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);
        $funcoes = $this->mergeFuncoesDisponiveis($funcoes, $request, null, $empresaId);

        if (!in_array($tipo, ['matriz', 'especifico'], true)) {
            abort(404);
        }

        $tipoLabel = $tipo === 'matriz' ? 'Matriz' : 'Específico';
        $artInfo = $this->artInfoParaCliente($cliente);
        $valorArt  = $artInfo['valor'] ?? 500.00;
        $artDisponivel = $artInfo['disponivel'];
        $funcaoQtdMap = $this->funcionarioCountByFuncao($cliente, $empresaId);

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
            'artDisponivel' => $artDisponivel,
            'funcaoQtdMap' => $funcaoQtdMap,
        ]);
    }

    public function pgrEdit(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        // relação na Tarefa: public function pgr() { return $this->hasOne(PgrSolicitacoes::class, 'tarefa_id'); }
        $pgr = $tarefa->pgr;
        abort_if(!$pgr, 404);

        $cliente = $tarefa->cliente;

        $tipo      = $pgr->tipo; // matriz|especifico
        $tipoLabel = $tipo === 'matriz' ? 'Matriz' : 'Específico';

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);
        $funcoes = $this->mergeFuncoesDisponiveis($funcoes, $request, $pgr->funcoes ?? null, $empresaId);

        // se quiser manter o valor já salvo, senão usa fixo
        $artInfo = $this->artInfoParaCliente($cliente);
        $valorArt = $pgr->valor_art ?? ($artInfo['valor'] ?? 500.00);
        $artDisponivel = $artInfo['disponivel'];
        $funcaoQtdMap = $this->funcionarioCountByFuncao($cliente, $empresaId);

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
            'artDisponivel' => $artDisponivel,
            'funcaoQtdMap' => $funcaoQtdMap,
        ]);
    }

    public function pgrUpdate(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $pgr = $tarefa->pgr;
        abort_if(!$pgr, 404);

        $cliente = $tarefa->cliente;

        // mesma validação do store
        $messages = [
            'tipo.required' => 'Selecione o tipo do PGR.',
            'tipo.in' => 'Selecione um tipo de PGR valido.',
            'com_art.required' => 'Informe se o PGR tera ART.',
            'com_art.boolean' => 'Informe um valor valido para ART.',
            'qtd_homens.required' => 'Informe a quantidade de funcionarios homens.',
            'qtd_homens.integer' => 'Informe um numero valido para funcionarios homens.',
            'qtd_homens.min' => 'A quantidade de funcionarios homens nao pode ser negativa.',
            'qtd_mulheres.required' => 'Informe a quantidade de funcionarias mulheres.',
            'qtd_mulheres.integer' => 'Informe um numero valido para funcionarias mulheres.',
            'qtd_mulheres.min' => 'A quantidade de funcionarias mulheres nao pode ser negativa.',
            'funcoes.required' => 'Adicione ao menos uma funcao.',
            'funcoes.array' => 'Formato invalido para funcoes.',
            'funcoes.min' => 'Adicione ao menos uma funcao.',
            'funcoes.*.funcao_id.required' => 'Selecione a funcao.',
            'funcoes.*.funcao_id.exists' => 'Funcao invalida.',
            'funcoes.*.quantidade.required' => 'Informe a quantidade da funcao.',
            'funcoes.*.quantidade.integer' => 'Informe um numero valido para a quantidade.',
            'funcoes.*.quantidade.min' => 'A quantidade da funcao deve ser pelo menos 1.',
        ];

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
            'funcoes.*.nr_altura'     => ['nullable', 'boolean'],
            'funcoes.*.nr_eletricidade' => ['nullable', 'boolean'],
            'funcoes.*.nr_espaco_confinado' => ['nullable', 'boolean'],
            'funcoes.*.nr_definido'   => ['nullable', 'boolean'],
        
        ], $messages);

        if ((bool) $data['com_art'] && !$this->artInfoParaCliente($cliente)['disponivel']) {
            return back()
                ->withInput()
                ->withErrors(['com_art' => 'ART não está disponível no contrato do cliente.']);
        }

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
        $artInfo = $this->artInfoParaCliente($cliente);
        $valorArt  = $data['com_art'] ? ($artInfo['valor'] ?? 500.00) : null;

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
            if ($pgr->com_art) {
                $descricao .= ' (COM ART)';
            }
            if ($pgr->com_pcms0) {
                $descricao .= ' + PCMSO';
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

        if ($request->hasFile('anexos')) {
            Anexos::salvarDoRequest($request, 'anexos', [
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente?->id,
                'tarefa_id'      => $tarefa->id,
                'funcionario_id' => null,
                'uploaded_by'    => $usuario->id,
                'servico'        => 'PGR',
                'subpath'        => 'anexos-custom/' . $empresaId,
            ]);
        }

        $origem = $request->query('origem', $request->input('origem'));
        if ($origem === 'cliente' || $usuario->isCliente()) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'PGR atualizado com sucesso.');
        }

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

        $messages = [
            'tipo.required' => 'Selecione o tipo do PGR.',
            'tipo.in' => 'Selecione um tipo de PGR valido.',
            'com_art.required' => 'Informe se o PGR tera ART.',
            'com_art.boolean' => 'Informe um valor valido para ART.',
            'qtd_homens.required' => 'Informe a quantidade de funcionarios homens.',
            'qtd_homens.integer' => 'Informe um numero valido para funcionarios homens.',
            'qtd_homens.min' => 'A quantidade de funcionarios homens nao pode ser negativa.',
            'qtd_mulheres.required' => 'Informe a quantidade de funcionarias mulheres.',
            'qtd_mulheres.integer' => 'Informe um numero valido para funcionarias mulheres.',
            'qtd_mulheres.min' => 'A quantidade de funcionarias mulheres nao pode ser negativa.',
            'funcoes.required' => 'Adicione ao menos uma funcao.',
            'funcoes.array' => 'Formato invalido para funcoes.',
            'funcoes.min' => 'Adicione ao menos uma funcao.',
            'funcoes.*.funcao_id.required' => 'Selecione a funcao.',
            'funcoes.*.funcao_id.exists' => 'Funcao invalida.',
            'funcoes.*.quantidade.required' => 'Informe a quantidade da funcao.',
            'funcoes.*.quantidade.integer' => 'Informe um numero valido para a quantidade.',
            'funcoes.*.quantidade.min' => 'A quantidade da funcao deve ser pelo menos 1.',
        ];

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
            'funcoes.*.nr_altura'     => ['nullable', 'boolean'],
            'funcoes.*.nr_eletricidade' => ['nullable', 'boolean'],
            'funcoes.*.nr_espaco_confinado' => ['nullable', 'boolean'],
            'funcoes.*.nr_definido'   => ['nullable', 'boolean'],
        
        ], $messages);

        if ((bool) $data['com_art'] && !$this->artInfoParaCliente($cliente)['disponivel']) {
            return back()
                ->withInput()
                ->withErrors(['com_art' => 'ART não está disponível no contrato do cliente.']);
        }

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
        $artInfo = $this->artInfoParaCliente($cliente);
        $valorArt  = $data['com_art'] ? ($artInfo['valor'] ?? 500.00) : null;

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
            $inicioPrevisto = now();
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, optional($servicoPgr)->id);

            // cria Tarefa base
            $tarefa = Tarefa::create([
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'responsavel_id' => $usuario->id,
                'coluna_id'      => optional($colunaInicial)->id,
                'servico_id'     => optional($servicoPgr)->id,
                'titulo'         => "PGR - {$tipoLabel}",
                'descricao'      => "PGR - {$tipoLabel}" . ($data['com_art'] ? ' (COM ART)' : ''),
                'inicio_previsto'=> $inicioPrevisto,
                'fim_previsto'   => $fimPrevisto,
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

        if ($request->hasFile('anexos')) {
            Anexos::salvarDoRequest($request, 'anexos', [
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'tarefa_id'      => $tarefaId,
                'funcionario_id' => null,
                'uploaded_by'    => $usuario->id,
                'servico'        => 'PGR',
                'subpath'        => 'anexos-custom/' . $empresaId, // mesmo padrão dos outros
            ]);
        }

        // redireciona para pergunta de PCMSO
        $origem = $request->query('origem', $request->input('origem'));
        return redirect()
            ->route('operacional.kanban.pgr.pcmso', [
                'tarefa' => $tarefaId,
                'origem' => $origem,
            ]);
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
            'origem'  => $request->query('origem', $request->input('origem')),
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

        if (!empty($data['com_pcms0'])) {
            $contrato = app(\App\Services\ContratoClienteService::class)
                ->getContratoAtivo($tarefa->cliente_id, $empresaId, null);

            $servicoPcmsoId = \App\Models\Servico::query()
                ->where('empresa_id', $empresaId)
                ->where('nome', 'PCMSO')
                ->value('id');

            $itemPcmso = $contrato?->itens()
                ->where('servico_id', $servicoPcmsoId)
                ->where('ativo', true)
                ->first();

            if (!$contrato || !$servicoPcmsoId || !$itemPcmso || (float) $itemPcmso->preco_unitario_snapshot <= 0) {
                return back()
                    ->withErrors(['com_pcms0' => 'Serviço PCMSO não contratado. Converse com o comercial.'])
                    ->withInput();
            }
        }

        $pgr->update([
            'com_pcms0' => (bool)$data['com_pcms0'],
        ]);

        // monta uma descrição mais amigável pra aparecer no modal
        $descricao = "PGR - " . ($pgr->tipo === 'matriz' ? 'Matriz' : 'Específico');

        if ($pgr->com_art) {
            $descricao .= ' (COM ART)';
        }
        if ($pgr->com_pcms0) {
            $descricao .= ' + PCMSO';
        }

        $tarefa->update([
            'descricao' => $descricao,
        ]);

        $origem = $request->query('origem', $request->input('origem'));
        if ($origem === 'cliente' || $usuario->isCliente()) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'Solicitação de PGR criada com sucesso e enviada para análise.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa PGR criada e enviada para a coluna Pendente.');
    }

    private function artInfoParaCliente(Cliente $cliente): array
    {
        $contrato = app(ContratoClienteService::class)
            ->getContratoAtivo($cliente->id, $cliente->empresa_id, null);

        if (!$contrato) {
            return ['disponivel' => false, 'valor' => null];
        }

        $servicoArtId = Servico::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('nome', 'ART')
            ->value('id');

        if (!$servicoArtId) {
            return ['disponivel' => false, 'valor' => null];
        }

        $item = $contrato->itens()
            ->where('servico_id', $servicoArtId)
            ->where('ativo', true)
            ->first();

        if (!$item) {
            return ['disponivel' => false, 'valor' => null];
        }

        return [
            'disponivel' => true,
            'valor' => (float) $item->preco_unitario_snapshot,
        ];
    }

    private function mergeFuncoesDisponiveis($funcoes, Request $request, ?array $funcoesSalvas, int $empresaId)
    {
        $ids = collect($request->old('funcoes', $funcoesSalvas ?? []))
            ->pluck('funcao_id')
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return $funcoes;
        }

        $presentIds = collect($funcoes)->pluck('id')->map(function ($id) {
            return (int) $id;
        });

        $missingIds = $ids->diff($presentIds);

        if ($missingIds->isEmpty()) {
            return $funcoes;
        }

        $extras = Funcao::query()
            ->daEmpresa($empresaId)
            ->whereIn('id', $missingIds)
            ->get();

        if ($extras->isEmpty()) {
            return $funcoes;
        }

        return collect($funcoes)
            ->concat($extras)
            ->unique('id')
            ->values();
    }

    private function funcionarioCountByFuncao(Cliente $cliente, int $empresaId): array
    {
        return Funcionario::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->where('ativo', true)
            ->whereNotNull('funcao_id')
            ->selectRaw('funcao_id, COUNT(*) as total')
            ->groupBy('funcao_id')
            ->pluck('total', 'funcao_id')
            ->map(function ($total) {
                return (int) $total;
            })
            ->toArray();
    }
}
