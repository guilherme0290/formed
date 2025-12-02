<?php

// app/Http/Controllers/Cliente/ClienteFuncionarioController.php

namespace App\Http\Controllers\Cliente;


use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClienteFuncionarioController extends Controller
{
    public function index(Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        $cliente = Cliente::where('empresa_id', $empresaId)->firstOrFail();

        $q = trim((string) $request->query('q', ''));

        $funcionarios = Funcionario::where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->when($q, function ($w) use ($q) {
                $doc = preg_replace('/\D+/', '', $q);
                $w->where(function ($s) use ($q, $doc) {
                    $s->where('nome', 'like', "%{$q}%")
                        ->orWhere('cpf', 'like', "%{$doc}%");
                });
            })
            ->orderBy('nome')
            ->paginate(12)
            ->withQueryString();

        return view('clientes.funcionarios.index', [
            'cliente'      => $cliente,
            'funcionarios' => $funcionarios,
            'q'            => $q,
        ]);
    }

    public function create(Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        $cliente = Cliente::where('empresa_id', $empresaId)->firstOrFail();

        return view('cliente.funcionarios.form', [
            'cliente'    => $cliente,
            'funcionario'=> null,
            'tab'        => 'geral',
        ]);
    }

    public function store(Request $r)
    {
        $user      = Auth::user();
        $empresaId = $user->empresa_id;

        $cliente = Cliente::where('empresa_id', $empresaId)->firstOrFail();

        $dados = $r->validate([
            'nome'            => ['required', 'string', 'max:255'],
            'cpf'             => ['nullable', 'string', 'max:14'],
            'rg'              => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'data_admissao'   => ['nullable', 'date'],
            'funcao'          => ['nullable', 'string', 'max:255'],

            'treinamento_nr'  => ['nullable', 'boolean'],
            'exame_admissional'      => ['nullable', 'boolean'],
            'exame_periodico'        => ['nullable', 'boolean'],
            'exame_demissional'      => ['nullable', 'boolean'],
            'exame_mudanca_funcao'   => ['nullable', 'boolean'],
            'exame_retorno_trabalho' => ['nullable', 'boolean'],
        ]);

        // checkboxes
        $dados['treinamento_nr']        = $r->boolean('treinamento_nr');
        $dados['exame_admissional']     = $r->boolean('exame_admissional');
        $dados['exame_periodico']       = $r->boolean('exame_periodico');
        $dados['exame_demissional']     = $r->boolean('exame_demissional');
        $dados['exame_mudanca_funcao']  = $r->boolean('exame_mudanca_funcao');
        $dados['exame_retorno_trabalho']= $r->boolean('exame_retorno_trabalho');

        $dados['empresa_id'] = $empresaId;
        $dados['cliente_id'] = $cliente->id;

        $funcionario = Funcionario::create($dados);

        return redirect()
            ->route('cliente.funcionarios.show', $funcionario)
            ->with('ok', 'Funcionário cadastrado com sucesso.');
    }

    public function show(Funcionario $funcionario, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($funcionario->empresa_id !== $empresaId, 403);

        $cliente = $funcionario->cliente;

        $tab = $request->query('tab', 'geral');

        // Documentos do funcionário (somente leitura para o cliente)
        $documentos = $funcionario->documentos()
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cliente.funcionarios.show', [
            'cliente'    => $cliente,
            'funcionario'=> $funcionario,
            'tab'        => $tab,
            'documentos' => $documentos,
        ]);
    }
}

