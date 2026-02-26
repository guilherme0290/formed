<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\Tarefa;
use App\Services\AsoGheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClienteFuncionarioController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Cliente em contexto do portal
        $clienteId = $request->session()->get('portal_cliente_id');

        if (!$clienteId) {
            abort(403, 'NENHUM CLIENTE FOI SELECIONADO PARA O PORTAL.');
        }

        $cliente = Cliente::findOrFail($clienteId);

        $q        = trim((string) $request->query('q', ''));
        $status   = $request->query('status', 'todos'); // todos|ativos|inativos
        $funcaoId = $request->integer('funcao_id');

        $funcionariosQuery = Funcionario::query()
            ->with('funcao') // <<< ADICIONADO: carrega a função junto
            ->where('cliente_id', $cliente->id);

        // busca por nome / CPF
        if ($q !== '') {
            $doc = preg_replace('/\D+/', '', $q);

            $funcionariosQuery->where(function ($w) use ($q, $doc) {
                $w->where('nome', 'like', "%{$q}%")
                    ->orWhere('cpf', 'like', "%{$doc}%");
                // se quiser buscar por função, depois fazemos via relacionamento
            });
        }

        if ($funcaoId) {
            $funcionariosQuery->where('funcao_id', $funcaoId);
        }

        // filtro de status
        if ($status !== 'todos') {
            $funcionariosQuery->where('ativo', $status === 'ativos' ? 1 : 0);
        }

        $funcionarios = $funcionariosQuery
            ->orderBy('nome')
            ->paginate(20)
            ->withQueryString();

        // contagens para os cards
        $totalAtivos       = Funcionario::where('cliente_id', $cliente->id)->where('ativo', 1)->count();
        $totalInativos     = Funcionario::where('cliente_id', $cliente->id)->where('ativo', 0)->count();
        $totalDocsVencidos = 0; // placeholder
        $totalDocsAVencer  = 0; // placeholder

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($cliente->empresa_id, $cliente->id);

        $funcionariosBusca = Funcionario::query()
            ->where('cliente_id', $cliente->id)
            ->orderBy('nome')
            ->pluck('nome');

        return view('clientes.funcionarios.index', [
            'cliente'           => $cliente,
            'funcionarios'      => $funcionarios,
            'funcionariosBusca' => $funcionariosBusca,
            'q'                 => $q,
            'status'            => $status,
            'funcaoId'          => $funcaoId,
            'funcoes'           => $funcoes,
            'totalAtivos'       => $totalAtivos,
            'totalInativos'     => $totalInativos,
            'totalDocsVencidos' => $totalDocsVencidos,
            'totalDocsAVencer'  => $totalDocsAVencer,
        ]);
    }

    /**
     * Form de novo funcionário.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->cliente_id) {
            abort(403, 'USUÁRIO NÃO ESTÁ VINCULADO A UM CLIENTE.');
        }

        $cliente = Cliente::findOrFail($user->cliente_id);

        // carrega as funções da empresa para o select
        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($user->empresa_id, $cliente->id);

        return view('clientes.funcionarios.form', [
            'cliente'     => $cliente,
            'funcionario' => null,
            'funcoes'     => $funcoes,
            'tab'         => 'geral',
            'modo'        => 'create',
        ]);
    }

    public function edit(Request $request, Funcionario $funcionario)
    {
        $user = Auth::user();

        if (!$user || !$user->cliente_id) {
            abort(403, 'USUÁRIO NÃO ESTÁ VINCULADO A UM CLIENTE.');
        }

        $cliente = Cliente::findOrFail($user->cliente_id);

        if ($funcionario->cliente_id !== $cliente->id) {
            abort(403, 'Funcionário não pertence a este cliente.');
        }

        $funcoes = app(AsoGheService::class)
            ->funcoesDisponiveisParaCliente($user->empresa_id, $cliente->id);

        return view('clientes.funcionarios.form', [
            'cliente'     => $cliente,
            'funcionario' => $funcionario,
            'funcoes'     => $funcoes,
            'tab'         => 'geral',
            'modo'        => 'edit',
        ]);
    }

    /**
     * Salva novo funcionário.
     */
    public function store(Request $r)
    {
        $user = Auth::user();

        if (!$user || !$user->cliente_id) {
            abort(403, 'USUÁRIO NÃO ESTÁ VINCULADO A UM CLIENTE.');
        }

        $cliente = Cliente::findOrFail($user->cliente_id);

        $dados = $r->validate([
            'nome'            => ['required', 'string', 'max:255'],
            'cpf'             => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('funcionarios', 'cpf')->where('cliente_id', $cliente->id),
            ],
            'rg'              => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'data_admissao'   => ['nullable', 'date'],
            'celular'         => ['nullable', 'string', 'max:20'],
            'setor'           => ['nullable', 'string', 'max:100'],

            // vindo do componente
            'funcao_id'       => ['nullable', 'integer', 'exists:funcoes,id'],

            'treinamento_nr'          => ['nullable', 'boolean'],
            'exame_admissional'      => ['nullable', 'boolean'],
            'exame_periodico'        => ['nullable', 'boolean'],
            'exame_demissional'      => ['nullable', 'boolean'],
            'exame_mudanca_funcao'   => ['nullable', 'boolean'],
            'exame_retorno_trabalho' => ['nullable', 'boolean'],
        ]);

        // checkboxes
        $dados['cpf']                   = !empty($dados['cpf'])
            ? preg_replace('/\D+/', '', $dados['cpf'])
            : null;
        $dados['celular']               = !empty($dados['celular'])
            ? preg_replace('/\D+/', '', $dados['celular'])
            : null;
        $dados['treinamento_nr']         = $r->boolean('treinamento_nr');
        $dados['exame_admissional']      = $r->boolean('exame_admissional');
        $dados['exame_periodico']        = $r->boolean('exame_periodico');
        $dados['exame_demissional']      = $r->boolean('exame_demissional');
        $dados['exame_mudanca_funcao']   = $r->boolean('exame_mudanca_funcao');
        $dados['exame_retorno_trabalho'] = $r->boolean('exame_retorno_trabalho');

        // vínculo correto
        $dados['cliente_id'] = $cliente->id;
        $dados['empresa_id'] = $cliente->empresa_id;

        $funcionario = Funcionario::create($dados);

        return redirect()
            ->route('cliente.funcionarios.show', $funcionario)
            ->with('ok', 'Funcionário cadastrado com sucesso.');
    }

    public function update(Request $r, Funcionario $funcionario)
    {
        $user = Auth::user();

        if (!$user || !$user->cliente_id) {
            abort(403, 'USUÁRIO NÃO ESTÁ VINCULADO A UM CLIENTE.');
        }

        $cliente = Cliente::findOrFail($user->cliente_id);

        if ($funcionario->cliente_id !== $cliente->id) {
            abort(403, 'Funcionário não pertence a este cliente.');
        }

        $dados = $r->validate([
            'nome'            => ['required', 'string', 'max:255'],
            'cpf'             => [
                'nullable',
                'string',
                'max:14',
                Rule::unique('funcionarios', 'cpf')
                    ->where('cliente_id', $cliente->id)
                    ->ignore($funcionario->id),
            ],
            'rg'              => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'data_admissao'   => ['nullable', 'date'],
            'celular'         => ['nullable', 'string', 'max:20'],
            'setor'           => ['nullable', 'string', 'max:100'],
            'funcao_id'       => ['nullable', 'integer', 'exists:funcoes,id'],
            'treinamento_nr'          => ['nullable', 'boolean'],
            'exame_admissional'      => ['nullable', 'boolean'],
            'exame_periodico'        => ['nullable', 'boolean'],
            'exame_demissional'      => ['nullable', 'boolean'],
            'exame_mudanca_funcao'   => ['nullable', 'boolean'],
            'exame_retorno_trabalho' => ['nullable', 'boolean'],
        ]);

        $dados['cpf']                   = !empty($dados['cpf'])
            ? preg_replace('/\D+/', '', $dados['cpf'])
            : null;
        $dados['celular']               = !empty($dados['celular'])
            ? preg_replace('/\D+/', '', $dados['celular'])
            : null;
        $dados['treinamento_nr']         = $r->boolean('treinamento_nr');
        $dados['exame_admissional']      = $r->boolean('exame_admissional');
        $dados['exame_periodico']        = $r->boolean('exame_periodico');
        $dados['exame_demissional']      = $r->boolean('exame_demissional');
        $dados['exame_mudanca_funcao']   = $r->boolean('exame_mudanca_funcao');
        $dados['exame_retorno_trabalho'] = $r->boolean('exame_retorno_trabalho');

        $funcionario->update($dados);

        return redirect()
            ->route('cliente.funcionarios.show', $funcionario)
            ->with('ok', 'Funcionário atualizado com sucesso.');
    }

    /**
     * Detalhes do funcionário.
     */
    public function show(Request $request, Funcionario $funcionario)
    {
        // Cliente em contexto do portal
        $clienteId = $request->session()->get('portal_cliente_id');

        if (!$clienteId) {
            abort(403, 'NENHUM CLIENTE FOI SELECIONADO PARA O PORTAL.');
        }

        $cliente = Cliente::findOrFail($clienteId);

        // garante que o funcionário é desse cliente
        if ($funcionario->cliente_id !== $cliente->id) {
            abort(403, 'Funcionário não pertence a este cliente.');
        }

        // <<< ADICIONADO: garantir que a função esteja carregada na tela de detalhes
        $funcionario->load('funcao');

        $arquivosAso = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->where(function ($q) use ($funcionario) {
                $q->where(function ($asoQ) use ($funcionario) {
                    $asoQ->where('funcionario_id', $funcionario->id)
                        ->whereHas('asoSolicitacao');
                })->orWhereHas('treinamentoNr', function ($trQ) use ($funcionario) {
                    $trQ->where('funcionario_id', $funcionario->id);
                });
            })
            ->where(function ($q) {
                $q->whereNotNull('path_documento_cliente')
                    ->orWhereHas('anexos', function ($aq) {
                        $aq->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['certificado_treinamento']);
                    });
            })
            ->with(['coluna', 'servico', 'asoSolicitacao', 'anexos'])
            ->orderByDesc('finalizado_em')
            ->orderByDesc('updated_at')
            ->get();

        return view('clientes.funcionarios.show', compact('cliente', 'funcionario', 'arquivosAso'));
    }

    public function toggleStatus(Request $request, Funcionario $funcionario)
    {
        // garante que o funcionário é do cliente logado no portal
        $clienteIdSessao = $request->session()->get('portal_cliente_id');

        abort_unless(
            $clienteIdSessao && (int) $funcionario->cliente_id === (int) $clienteIdSessao,
            403
        );

        // alterna o status
        $funcionario->ativo = ! $funcionario->ativo;
        $funcionario->save();

        $mensagem = $funcionario->ativo
            ? 'Funcionário reativado com sucesso.'
            : 'Funcionário inativado com sucesso.';

        return redirect()
            ->route('cliente.funcionarios.show', $funcionario)
            ->with('ok', $mensagem);
    }

    public function destroy(Request $request, Funcionario $funcionario)
    {
        $clienteIdSessao = $request->session()->get('portal_cliente_id');

        abort_unless(
            $clienteIdSessao && (int) $funcionario->cliente_id === (int) $clienteIdSessao,
            403
        );

        $funcionario->delete();

        return redirect()
            ->route('cliente.funcionarios.index')
            ->with('ok', 'Funcionário removido com sucesso.');
    }

}
