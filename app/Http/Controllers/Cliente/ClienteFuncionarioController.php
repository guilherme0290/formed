<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Http\Request;

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

        $q      = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'todos'); // todos|ativos|inativos

        $funcionariosQuery = Funcionario::query()
            ->where('cliente_id', $cliente->id);

        // busca por nome / CPF / função
        if ($q !== '') {
            $doc = preg_replace('/\D+/', '', $q);

            $funcionariosQuery->where(function ($w) use ($q, $doc) {
                $w->where('nome', 'like', "%{$q}%")
                    ->orWhere('cpf', 'like', "%{$doc}%")
                    ->orWhere('funcao', 'like', "%{$q}%");
            });
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

        return view('cliente.funcionarios.index', [
            'cliente'           => $cliente,
            'funcionarios'      => $funcionarios,
            'q'                 => $q,
            'status'            => $status,
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

        return view('cliente.funcionarios.form', [
            'cliente'     => $cliente,
            'funcionario' => null,
            'tab'         => 'geral',
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
            'cpf'             => ['nullable', 'string', 'max:14'],
            'rg'              => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'data_admissao'   => ['nullable', 'date'],
            'funcao'          => ['nullable', 'string', 'max:255'],

            'treinamento_nr'          => ['nullable', 'boolean'],
            'exame_admissional'      => ['nullable', 'boolean'],
            'exame_periodico'        => ['nullable', 'boolean'],
            'exame_demissional'      => ['nullable', 'boolean'],
            'exame_mudanca_funcao'   => ['nullable', 'boolean'],
            'exame_retorno_trabalho' => ['nullable', 'boolean'],
        ]);

        // checkboxes
        $dados['treinamento_nr']          = $r->boolean('treinamento_nr');
        $dados['exame_admissional']      = $r->boolean('exame_admissional');
        $dados['exame_periodico']        = $r->boolean('exame_periodico');
        $dados['exame_demissional']      = $r->boolean('exame_demissional');
        $dados['exame_mudanca_funcao']   = $r->boolean('exame_mudanca_funcao');
        $dados['exame_retorno_trabalho'] = $r->boolean('exame_retorno_trabalho');

        // vínculo correto
        $dados['cliente_id'] = $cliente->id;

        $funcionario = Funcionario::create($dados);

        return redirect()
            ->route('cliente.funcionarios.show', $funcionario)
            ->with('ok', 'Funcionário cadastrado com sucesso.');
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

        return view('cliente.funcionarios.show', compact('cliente', 'funcionario'));
    }

    // Se quiser manter create/store depois, fazemos usando o mesmo cliente da sessão
}
