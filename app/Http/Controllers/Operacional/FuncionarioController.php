<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FuncionarioController extends Controller
{
    public function create(Cliente $cliente)
    {
        $user = Auth::user();

        return view('operacional.funcionarios.create', [
            'usuario' => $user,
            'cliente' => $cliente,
        ]);
    }

    public function store(Request $r, Cliente $cliente)
    {
        $user = Auth::user();

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

        // checkboxes vêm como "on" -> converto pra bool
        $dados['treinamento_nr']        = $r->boolean('treinamento_nr');
        $dados['exame_admissional']     = $r->boolean('exame_admissional');
        $dados['exame_periodico']       = $r->boolean('exame_periodico');
        $dados['exame_demissional']     = $r->boolean('exame_demissional');
        $dados['exame_mudanca_funcao']  = $r->boolean('exame_mudanca_funcao');
        $dados['exame_retorno_trabalho']= $r->boolean('exame_retorno_trabalho');

        $dados['empresa_id'] = $user->empresa_id;
        $dados['cliente_id'] = $cliente->id;

        Funcionario::create($dados);

        return redirect()
            ->route('operacional.kanban') // ou outra rota que você quiser
            ->with('ok', 'Funcionário ligado ao cliente e pronto para agendar exame.');
    }
}
