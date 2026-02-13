<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Operacional\Concerns\ValidatesClientePortalTaskEditing;
use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\AsoGheService;
use App\Models\KanbanColuna;
use App\Models\LtcatSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Services\TempoTarefaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LtcatController extends Controller
{
    use ValidatesClientePortalTaskEditing;

    // Tela "LTCAT - Selecione o Tipo"
    public function selecionarTipo(Cliente $cliente, Request $request)
    {
        $origem = $request->query('origem');

        return view('operacional.kanban.ltcat.tipo', [
            'cliente' => $cliente,
            'origem'  => $origem,
        ]);
    }

    // Formulário LTCAT (Matriz / Específico)
    public function create(Cliente $cliente, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $origem = $request->query('origem');

        $tipo = $request->query('tipo'); // matriz | especifico
        abort_unless(in_array($tipo, ['matriz', 'especifico'], true), 404);

        $tipoLabel = $tipo === 'matriz' ? 'Matriz' : 'Específico';

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);

        // base para o formulário de funções
        $funcoesForm = old('funcoes');
        if (empty($funcoesForm) || !is_array($funcoesForm)) {
            $funcoesForm = [
                ['funcao_id' => null, 'quantidade' => 1],
            ];
        }

        return view('operacional.kanban.ltcat.create', [
            'cliente'      => $cliente,
            'tipo'         => $tipo,
            'tipoLabel'    => $tipoLabel,
            'funcoes'      => $funcoes,
            'funcoesForm'  => $funcoesForm,
            'ltcat'        => null,
            'isEdit'       => false,
            'origem'       => $origem,

        ]);
    }

    public function edit(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $ltcat = $tarefa->ltcatSolicitacao;
        $cliente   = $ltcat->cliente;
        $tipo      = $ltcat->tipo === 'especifico' ? 'especifico' : 'matriz';
        $tipoLabel = $tipo === 'matriz' ? 'Matriz' : 'Específico';

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($empresaId, $cliente->id);

        // usa old() se tiver erro de validação, senão o que veio do banco
        $funcoesForm = old('funcoes', $ltcat->funcoes ?? []);

        if (empty($funcoesForm) || !is_array($funcoesForm)) {
            $funcoesForm = [
                ['funcao_id' => null, 'quantidade' => 1],
            ];
        }

        return view('operacional.kanban.ltcat.create', [
            'cliente'      => $cliente,
            'tipo'         => $tipo,
            'tipoLabel'    => $tipoLabel,
            'funcoes'      => $funcoes,
            'funcoesForm'  => $funcoesForm,
            'ltcat'        => $ltcat,
            'isEdit'       => true,
        ]);
    }

    public function update(LtcatSolicitacoes $ltcat, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $ltcat->tarefa)) {
            return $redirect;
        }

        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($ltcat->empresa_id !== $empresaId, 403);

        // mantém o tipo que já estava salvo
        $tipo = $ltcat->tipo === 'especifico' ? 'especifico' : 'matriz';
        $request->merge(['tipo' => $tipo]);

        $data = $request->validate([
            'tipo'                => ['required', 'in:matriz,especifico'],

            // Matriz
            'endereco_avaliacoes' => ['required_if:tipo,matriz', 'nullable', 'string'],

            // Específico
            'nome_obra'           => ['required_if:tipo,especifico', 'nullable', 'string', 'max:255'],
            'cnpj_contratante'    => ['required_if:tipo,especifico', 'nullable', 'string', 'max:20'],
            'cei_cno'             => ['required_if:tipo,especifico', 'nullable', 'string', 'max:50'],
            'endereco_obra'       => ['required_if:tipo,especifico', 'nullable', 'string'],

            // Funções
            'funcoes'             => ['required', 'array', 'min:1'],
            'funcoes.*.funcao_id' => ['required', 'integer', 'exists:funcoes,id'],
            'funcoes.*.quantidade'=> ['required', 'integer', 'min:1'],
        ]);

        $totalFuncoes = count($data['funcoes']);
        $totalFuncionarios = collect($data['funcoes'])->sum(function ($f) {
            return (int)($f['quantidade'] ?? 0);
        });

        if ($totalFuncionarios <= 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'funcoes' => 'Informe pelo menos 1 funcionário nas funções.',
                ]);
        }

        DB::transaction(function () use (
            $ltcat,
            $data,
            $tipo,
            $totalFuncoes,
            $totalFuncionarios,
            $user
        ) {
            $payload = [
                'tipo'               => $tipo,
                'funcoes'            => $data['funcoes'],   // ✅ agora salva os dados, não as regras
                'total_funcoes'      => $totalFuncoes,
                'total_funcionarios' => $totalFuncionarios,
            ];

            if ($tipo === 'matriz') {
                $payload['endereco_avaliacoes'] = $data['endereco_avaliacoes'] ?? null;
                // zera infos de obra
                $payload['nome_obra']        = null;
                $payload['cnpj_contratante'] = null;
                $payload['cei_cno']          = null;
                $payload['endereco_obra']    = null;
            } else {
                // zera endereço de avaliações e preenche dados da obra
                $payload['endereco_avaliacoes'] = '';
                $payload['nome_obra']           = $data['nome_obra'] ?? null;
                $payload['cnpj_contratante']    = $data['cnpj_contratante'] ?? null;
                $payload['cei_cno']             = $data['cei_cno'] ?? null;
                $payload['endereco_obra']       = $data['endereco_obra'] ?? null;
            }

            $ltcat->update($payload);

            if ($ltcat->tarefa) {
                TarefaLog::create([
                    'tarefa_id'      => $ltcat->tarefa_id,
                    'user_id'        => $user->id,
                    'de_coluna_id'   => $ltcat->tarefa->coluna_id,
                    'para_coluna_id' => $ltcat->tarefa->coluna_id,
                    'acao'           => 'atualizado',
                    'observacao'     => 'LTCAT atualizado pelo usuário.',
                ]);
            }
        });

        $origem = $request->query('origem', $request->input('origem'));

        if ($origem === 'cliente') {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'LTCAT atualizado com sucesso!');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'LTCAT atualizado com sucesso!');

    }



    // Salvar LTCAT + criar Tarefa no Kanban
    public function store(Cliente $cliente, Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'tipo'                => ['required', 'in:matriz,especifico'],

            // Matriz
            'endereco_avaliacoes' => ['required_if:tipo,matriz', 'nullable', 'string'],

            // Específico
            'nome_obra'           => ['required_if:tipo,especifico', 'nullable', 'string', 'max:255'],
            'cnpj_contratante'    => ['required_if:tipo,especifico', 'nullable', 'string', 'max:20'],
            'cei_cno'             => ['required_if:tipo,especifico', 'nullable', 'string', 'max:50'],
            'endereco_obra'       => ['required_if:tipo,especifico', 'nullable', 'string'],

            // Funções
            'funcoes'                 => ['required', 'array', 'min:1'],
            'funcoes.*.funcao_id'     => ['required', 'integer', 'exists:funcoes,id'],
            'funcoes.*.quantidade'    => ['required', 'integer', 'min:1'],
        ]);

        $totalFuncoes = count($data['funcoes']);
        $totalFuncionarios = collect($data['funcoes'])->sum(function ($f) {
            return (int)($f['quantidade'] ?? 0);
        });

        if ($totalFuncionarios <= 0) {
            return back()
                ->withInput()
                ->withErrors([
                    'funcoes' => 'Informe pelo menos 1 funcionário nas funções.',
                ]);
        }

        // Coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // Serviço LTCAT
        $servicoLtcat = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'LTCAT')
            ->first();

        $tipoLabel = $data['tipo'] === 'matriz' ? 'Matriz' : 'Específico';

        $tarefaId = null;



        DB::transaction(function () use (
            $data,
            $empresaId,
            $cliente,
            $user,
            $colunaInicial,
            $servicoLtcat,
            $tipoLabel,
            $totalFuncoes,
            $totalFuncionarios,
            &$tarefaId
        ) {
            $inicioPrevisto = now();
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, optional($servicoLtcat)->id);

            // Cria Tarefa no Kanban
            $tarefa = Tarefa::create([
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'responsavel_id' => $user->id,
                'coluna_id'      => optional($colunaInicial)->id,
                'servico_id'     => optional($servicoLtcat)->id,
                'titulo'         => "LTCAT - {$tipoLabel}",
                'descricao'      => "LTCAT - {$tipoLabel}",
                'inicio_previsto'=> $inicioPrevisto,
                'fim_previsto'   => $fimPrevisto,
            ]);

            $tarefaId = $tarefa->id;

            // Monta payload base
            $payload = [
                'empresa_id'        => $empresaId,
                'cliente_id'        => $cliente->id,
                'tarefa_id'         => $tarefa->id,
                'responsavel_id'    => $user->id,

                'tipo'              => $data['tipo'],
                'funcoes'           => $data['funcoes'],
                'total_funcoes'     => $totalFuncoes,
                'total_funcionarios'=> $totalFuncionarios,
            ];

            if ($data['tipo'] === 'matriz') {
                // Matriz: usa endereço_avaliacoes, zera dados de obra
                $payload['endereco_avaliacoes'] = $data['endereco_avaliacoes'] ?? null;

                $payload['nome_obra']          = null;
                $payload['cnpj_contratante']   = null;
                $payload['cei_cno']            = null;
                $payload['endereco_obra']      = null;
            } else {
                // Específico: usa dados de obra, zera endereço_avaliacoes
                $payload['endereco_avaliacoes'] = '';

                $payload['nome_obra']          = $data['nome_obra'] ?? null;
                $payload['cnpj_contratante']   = $data['cnpj_contratante'] ?? null; // se quiser, aqui dá pra limpar máscara
                $payload['cei_cno']            = $data['cei_cno'] ?? null;
                $payload['endereco_obra']      = $data['endereco_obra'] ?? null;
            }

            // Cria registro LTCAT
            LtcatSolicitacoes::create($payload);


            // Log inicial da tarefa
            TarefaLog::create([
                'tarefa_id' => $tarefa->id,
                'user_id' => $user->id,
                'de_coluna_id' => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao' => 'criado',
                'observacao' => 'Tarefa LTCAT criada pelo usuário.',
            ]);
        });


        $origem = $request->input('origem');

        if ($origem === 'cliente') {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'Solicitação de LTCAT criada com sucesso e enviada para análise.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa LTCAT criada com sucesso!');
    }


}

