<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Tarefa;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TarefaLojaController extends Controller
{
    // Cliente existente
    public function storeExistente(Request $r)
    {
        $data = $r->validate([
            'cliente_id'    => ['required','exists:clientes,id'],
            'servico_id'    => ['required','exists:servicos,id'],
            'unidade_id'    => ['required','exists:unidades,id'],
            'titulo'        => ['required','string','max:255'],
            'descricao'     => ['nullable','string'],
            'prioridade'    => [Rule::in(['baixa','media','alta'])],
            'data_agendada' => ['required','date'],
            'hora_agendada' => ['required','date_format:H:i'],
        ]);

        $cliente = Cliente::findOrFail($data['cliente_id']);

        Tarefa::create([
            'empresa_id'     => $cliente->empresa_id,
            'cliente_id'     => $cliente->id,
            'servico_id'     => $data['servico_id'],
            'unidade_id'     => $data['unidade_id'],
            'responsavel_id' => $r->user()->id,
            'titulo'         => $data['titulo'],
            'descricao'      => $data['descricao'] ?? null,
            'prioridade'     => $data['prioridade'] ?? 'media',
            'status'         => Tarefa::PENDENTE,
            'data_agendada'  => $data['data_agendada'],
            'hora_agendada'  => $data['hora_agendada'],
        ]);

        return back()->with('ok','Tarefa criada para cliente existente.');
    }

    // Novo cliente (cria Cliente e a Tarefa)
    public function storeNovoCliente(Request $r)
    {
        $data = $r->validate([
            // cliente
            'razao_social'  => ['required','string','max:255'],
            'nome_fantasia' => ['nullable','string','max:255'],
            'cnpj'          => ['nullable','string','max:18'],
            'email'         => ['nullable','email'],
            'telefone'      => ['nullable','string','max:30'],
            'endereco'      => ['nullable','string','max:255'],
            // tarefa
            'servico_id'    => ['required','exists:servicos,id'],
            'unidade_id'    => ['required','exists:unidades,id'],
            'titulo'        => ['required','string','max:255'],
            'descricao'     => ['nullable','string'],
            'prioridade'    => [Rule::in(['baixa','media','alta'])],
            'data_agendada' => ['required','date'],
            'hora_agendada' => ['required','date_format:H:i'],
        ]);

        $cliente = Cliente::firstOrCreate(
            [
                'empresa_id' => $r->user()->empresa_id ?? null,
                'cnpj'       => $data['cnpj'] ?? null,
            ],
            [
                'razao_social'  => $data['razao_social'],
                'nome_fantasia' => $data['nome_fantasia'] ?? null,
                'email'         => $data['email'] ?? null,
                'telefone'      => $data['telefone'] ?? null,
                'endereco'      => $data['endereco'] ?? null,
                'ativo'         => true,
            ]
        );

        Tarefa::create([
            'empresa_id'     => $cliente->empresa_id,
            'cliente_id'     => $cliente->id,
            'servico_id'     => $data['servico_id'],
            'unidade_id'     => $data['unidade_id'],
            'responsavel_id' => $r->user()->id,
            'titulo'         => $data['titulo'],
            'descricao'      => $data['descricao'] ?? null,
            'prioridade'     => $data['prioridade'] ?? 'media',
            'status'         => Tarefa::PENDENTE,
            'data_agendada'  => $data['data_agendada'],
            'hora_agendada'  => $data['hora_agendada'],
        ]);

        return back()->with('ok','Cliente e tarefa criados.');
    }
}
