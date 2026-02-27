<?php

namespace App\Http\Controllers\Operacional;

use App\Helpers\S3Helper;
use App\Http\Controllers\Operacional\Concerns\ValidatesClientePortalTaskEditing;
use App\Http\Controllers\Controller;
use App\Models\Anexos;
use App\Models\Cliente;
use App\Models\Funcao;
use App\Models\Funcionario;
use App\Models\KanbanColuna;
use App\Models\PcmsoSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Services\AsoGheService;
use App\Services\TempoTarefaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PcmsoController extends Controller
{
    use ValidatesClientePortalTaskEditing;

    /**
     * Tela: PCMSO - Selecione o Tipo (Matriz / Específico)
     */
    public function selecionarTipo(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $origem = $request->query('origem'); // 'cliente' ou null

        return view('operacional.kanban.pcmso.tipo', [
            'cliente' => $cliente,
            'origem'  => $origem,
        ]);
    }

    /**
     * Tela: "Você já possui o PGR?" para Matriz/Específico
     */
    public function perguntaPgr(Cliente $cliente, string $tipo, Request $request)
    {
        $usuario = $request->user();
        abort_if($cliente->empresa_id !== $usuario->empresa_id, 403);

        $tipo = $tipo === 'especifico' ? 'especifico' : 'matriz';

        return view('operacional.kanban.pcmso.possui-pgr', [
            'cliente' => $cliente,
            'tipo'    => $tipo,
            'origem'  => $request->query('origem', $request->input('origem')),
        ]);
    }

    /**
     * Formulário para anexar PGR + (campos da obra se for específico)
     */
    public function createComPgr(Cliente $cliente, string $tipo, Request $request)
    {
        $usuario = $request->user();
        abort_if($cliente->empresa_id !== $usuario->empresa_id, 403);

        $tipo = $tipo === 'especifico' ? 'especifico' : 'matriz';

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($usuario->empresa_id, $cliente->id);
        $funcoes = $this->mergeFuncoesDisponiveis($funcoes, $request, null, $usuario->empresa_id);
        $funcaoQtdMap = $this->funcionarioCountByFuncao($cliente, $usuario->empresa_id);

        if ($tipo === 'matriz') {
            return view('operacional.kanban.pcmso.form_matriz', [
                'cliente' => $cliente,
                'tipo'    => $tipo,
                'funcoes' => $funcoes,
                'anexos'  => collect(),
                'origem'  => $request->query('origem', $request->input('origem')),
                'funcaoQtdMap' => $funcaoQtdMap,
            ]);
        }

        return view('operacional.kanban.pcmso.form_especifico', [
            'cliente' => $cliente,
            'tipo'    => $tipo,
            'funcoes' => $funcoes,
            'anexos'  => collect(),
            'origem'  => $request->query('origem', $request->input('origem')),
            'funcaoQtdMap' => $funcaoQtdMap,
        ]);
    }

    /**
     * Salvar PCMSO (Matriz ou Específico) com PGR anexado
     */
    public function storeComPgr(Cliente $cliente, string $tipo, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $tipo = $tipo === 'especifico' ? 'especifico' : 'matriz';

        // regras comuns
        $rules = [
            'pgr_arquivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'funcoes'                 => ['required', 'array', 'min:1'],
            'funcoes.*.funcao_id'     => ['required', 'integer', 'exists:funcoes,id'],
            'funcoes.*.quantidade'    => ['required', 'integer', 'min:1'],
            'funcoes.*.cbo'           => ['nullable', 'string', 'max:20'],
            'funcoes.*.descricao'     => ['nullable', 'string'],
        ];

        // se for específico, exige dados da obra
        if ($tipo === 'especifico') {
            $rules = array_merge($rules, [
                'obra_nome'              => ['required', 'string', 'max:255'],
                'obra_cnpj_contratante'  => ['string', 'max:20'],
                'obra_cei_cno'           => ['string', 'max:50'],
                'obra_endereco'          => ['string', 'max:255'],
            ]);
        }

        $messages = [
            'pgr_arquivo.required' => 'Anexe o arquivo do PGR em PDF.',
            'pgr_arquivo.file' => 'O arquivo do PGR precisa ser um arquivo valido.',
            'pgr_arquivo.mimes' => 'O arquivo do PGR deve ser um PDF.',
            'pgr_arquivo.max' => 'O arquivo do PGR deve ter no maximo 10MB.',
            'obra_nome.required' => 'Informe o nome da obra.',
            'funcoes.required' => 'Adicione ao menos uma funcao.',
            'funcoes.array' => 'Formato invalido para funcoes.',
            'funcoes.min' => 'Adicione ao menos uma funcao.',
            'funcoes.*.funcao_id.required' => 'Selecione a funcao.',
            'funcoes.*.funcao_id.exists' => 'Funcao invalida.',
            'funcoes.*.quantidade.required' => 'Informe a quantidade da funcao.',
            'funcoes.*.quantidade.integer' => 'Informe um numero valido para a quantidade.',
            'funcoes.*.quantidade.min' => 'A quantidade da funcao deve ser pelo menos 1.',
        ];

        $data = $request->validate($rules, $messages);

        // armazena PDF do PGR no S3
        // o campo pgr_arquivo_path vai guardar a "key" do S3 (ex: pcmso_pgr/1/arquivo.pdf)
        $path = S3Helper::upload(
            $request->file('pgr_arquivo'),
            'pcmso_pgr/' . $empresaId
        );

        // coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // serviço PCMSO (ajuste o nome se estiver diferente na tabela servicos)
        $servicoPcmso = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'PCMSO')
            ->first();

        $tipoLabel = $tipo === 'matriz' ? 'Matriz' : 'Específico';
        $prazoDias = 10;

        $tarefaId = null;

        DB::transaction(function () use (
            $empresaId,
            $cliente,
            $usuario,
            $colunaInicial,
            $servicoPcmso,
            $tipo,
            $tipoLabel,
            $prazoDias,
            $path,
            $data,
            &$tarefaId,
            $request
        ) {
            $inicioPrevisto = now();
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, optional($servicoPcmso)->id);

            // cria Tarefa no Kanban
            $tarefa = Tarefa::create([
                'empresa_id'      => $empresaId,
                'cliente_id'      => $cliente->id,
                'responsavel_id'  => $usuario->id,
                'coluna_id'       => optional($colunaInicial)->id,
                'servico_id'      => optional($servicoPcmso)->id,
                'titulo'          => "PCMSO - {$tipoLabel}",
                'descricao'       => "PCMSO - {$tipoLabel} com PGR anexado pelo cliente.",
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto'    => $fimPrevisto,
            ]);

            $tarefaId = $tarefa->id;

            // cria registro PCMSO
            PcmsoSolicitacoes::create([
                'empresa_id'            => $empresaId,
                'cliente_id'            => $cliente->id,
                'tarefa_id'             => $tarefa->id,
                'responsavel_id'        => $usuario->id,
                'tipo'                  => $tipo,
                'pgr_origem'            => 'arquivo_cliente',
                'pgr_arquivo_path'      => $path, // key do S3
                'funcoes'               => $data['funcoes'],
                'obra_nome'             => $data['obra_nome']             ?? null,
                'obra_cnpj_contratante' => $data['obra_cnpj_contratante'] ?? null,
                'obra_cei_cno'          => $data['obra_cei_cno']          ?? null,
                'obra_endereco'         => $data['obra_endereco']         ?? null,
                'prazo_dias'            => $prazoDias,
            ]);

            // log inicial da tarefa
            TarefaLog::create([
                'tarefa_id'     => $tarefa->id,
                'user_id'       => $usuario->id,
                'de_coluna_id'  => null,
                'para_coluna_id'=> optional($colunaInicial)->id,
                'acao'          => 'criado',
                'observacao'    => 'Tarefa PCMSO criada pelo usuário.',
            ]);

            // anexos adicionais (também já estão indo pro S3 via helper)
            Anexos::salvarDoRequest($request, 'anexos', [
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'tarefa_id'      => $tarefa->id,
                'funcionario_id' =>  null,
                'uploaded_by'    => auth()->user()->id,
                'servico'        => 'PCMSO',
                'subpath'        => 'anexos-custom/' . $empresaId,
            ]);
        });

        if ($usuario->isCliente()) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', "Solicitação de PCMSO {$tipoLabel} criada com sucesso e enviada para análise.");
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', "Tarefa PCMSO {$tipoLabel} criada com sucesso!");
    }

    public function edit(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $pcmso = $tarefa->pcmsoSolicitacao;
        abort_if(!$pcmso, 404);

        $anexos = Anexos::where('empresa_id', $empresaId)
            ->where('tarefa_id', $tarefa->id)
            ->where('servico', 'PCMSO')
            ->orderByDesc('created_at')
            ->get();

        $cliente = $tarefa->cliente;
        $tipo    = $pcmso->tipo === 'especifico' ? 'especifico' : 'matriz';

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);
        $funcoes = $this->mergeFuncoesDisponiveis($funcoes, $request, $pcmso->funcoes ?? null, $empresaId);
        $funcaoQtdMap = $this->funcionarioCountByFuncao($cliente, $empresaId);

        if ($tipo === 'matriz') {
            return view('operacional.kanban.pcmso.form_matriz', [
                'cliente' => $cliente,
                'tipo'    => $tipo,
                'funcoes' => $funcoes,
                'pcmso'   => $pcmso,
                'isEdit'  => true,
                'anexos'  => $anexos,
                'origem'  => $request->query('origem', $request->input('origem')),
                'funcaoQtdMap' => $funcaoQtdMap,
            ]);
        }

        return view('operacional.kanban.pcmso.form_especifico', [
            'cliente' => $cliente,
            'tipo'    => $tipo,
            'funcoes' => $funcoes,
            'pcmso'   => $pcmso,
            'isEdit'  => true,
            'anexos'  => $anexos,
            'origem'  => $request->query('origem', $request->input('origem')),
            'funcaoQtdMap' => $funcaoQtdMap,
        ]);
    }

    public function update(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $pcmso = $tarefa->pcmsoSolicitacao;
        abort_if(!$pcmso, 404);

        $cliente = $tarefa->cliente;
        $tipo    = $pcmso->tipo === 'especifico' ? 'especifico' : 'matriz';

        // regras comuns
        $rules = [
            'pgr_arquivo'    => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remover_arquivo'=> ['nullable', 'boolean'],
            'funcoes'                 => ['required', 'array', 'min:1'],
            'funcoes.*.funcao_id'     => ['required', 'integer', 'exists:funcoes,id'],
            'funcoes.*.quantidade'    => ['required', 'integer', 'min:1'],
            'funcoes.*.cbo'           => ['nullable', 'string', 'max:20'],
            'funcoes.*.descricao'     => ['nullable', 'string'],
        ];

        // se for específico, atualiza dados da obra também
        if ($tipo === 'especifico') {
            $rules = array_merge($rules, [
                'obra_nome'              => ['required', 'string', 'max:255'],
                'obra_cnpj_contratante'  => ['nullable', 'string', 'max:20'],
                'obra_cei_cno'           => ['nullable', 'string', 'max:50'],
                'obra_endereco'          => ['nullable', 'string', 'max:255'],
            ]);
        }

        $messages = [
            'pgr_arquivo.required' => 'Anexe o arquivo do PGR em PDF.',
            'pgr_arquivo.file' => 'O arquivo do PGR precisa ser um arquivo valido.',
            'pgr_arquivo.mimes' => 'O arquivo do PGR deve ser um PDF.',
            'pgr_arquivo.max' => 'O arquivo do PGR deve ter no maximo 10MB.',
            'obra_nome.required' => 'Informe o nome da obra.',
            'funcoes.required' => 'Adicione ao menos uma funcao.',
            'funcoes.array' => 'Formato invalido para funcoes.',
            'funcoes.min' => 'Adicione ao menos uma funcao.',
            'funcoes.*.funcao_id.required' => 'Selecione a funcao.',
            'funcoes.*.funcao_id.exists' => 'Funcao invalida.',
            'funcoes.*.quantidade.required' => 'Informe a quantidade da funcao.',
            'funcoes.*.quantidade.integer' => 'Informe um numero valido para a quantidade.',
            'funcoes.*.quantidade.min' => 'A quantidade da funcao deve ser pelo menos 1.',
        ];

        $data = $request->validate($rules, $messages);

        $disk = Storage::disk('s3');

        $pathAtual = $pcmso->pgr_arquivo_path;

        // se marcou para remover o arquivo
        $remover = (bool)($data['remover_arquivo'] ?? false);
        if ($remover && $pathAtual) {
            if ($disk->exists($pathAtual)) {
                $disk->delete($pathAtual);
            }
            $pathAtual = null;
        }

        // se subiu novo arquivo, substitui
        if ($request->hasFile('pgr_arquivo')) {
            if ($pathAtual && $disk->exists($pathAtual)) {
                $disk->delete($pathAtual);
            }

            $pathAtual = S3Helper::upload(
                $request->file('pgr_arquivo'),
                'pcmso_pgr/' . $empresaId
            );
        }

        // regra opcional: não deixar ficar sem PGR anexado
        if (!$pathAtual) {
            return back()
                ->withInput()
                ->withErrors(['pgr_arquivo' => 'E necessario manter um PGR anexado (envie um novo arquivo ou nao marque para remover).']);
        }

        // monta payload de atualização
        $updateData = [
            'pgr_arquivo_path' => $pathAtual, // key no S3
            'funcoes'          => $data['funcoes'],
        ];

        if ($tipo === 'especifico') {
            $updateData = array_merge($updateData, [
                'obra_nome'             => $data['obra_nome'],
                'obra_cnpj_contratante' => $data['obra_cnpj_contratante'] ?? null,
                'obra_cei_cno'          => $data['obra_cei_cno'] ?? null,
                'obra_endereco'         => $data['obra_endereco'] ?? null,
            ]);
        }

        // salva anexos adicionais (continua igual)
        Anexos::salvarDoRequest($request, 'anexos', [
            'empresa_id'     => $empresaId,
            'cliente_id'     => $cliente->id,
            'tarefa_id'      => $tarefa->id,
            'funcionario_id' => null,
            'uploaded_by'    => auth()->user()->id,
            'servico'        => 'PCMSO',
            'subpath'        => 'anexos-custom/' . $empresaId,
        ]);

        $pcmso->update($updateData);

        $origem = $request->query('origem', $request->input('origem'));
        if ($origem === 'cliente' || $usuario->isCliente()) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'PCMSO atualizado com sucesso!');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'PCMSO atualizado com sucesso!');
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
