<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TarefaLojaController extends Controller
{
    /**
     * Cliente EXISTENTE
     */
    public function storeExistente(Request $r)
    {
        $user = Auth::user();

        $data = $r->validate([
            // passo 1
            'cliente_id'     => ['required','exists:clientes,id'],
            'unidade_id'     => ['required'], // ajuste para exists:unidades,id se tiver essa tabela
            // passo 2
            'servico_id'     => ['required','exists:servicos,id'],
            'data'           => ['required','date'],
            'hora'           => ['required','string'],
            'prioridade'     => ['required', Rule::in(['baixa','media','alta'])],
            'prazo_sla'      => ['nullable','date'],
            'observacoes'    => ['nullable','string'],
            // passo 3
            'status_inicial' => ['required','string'], // slug da coluna (pendente, em_execucao, etc)
        ]);

        $servico = Servico::findOrFail($data['servico_id']);

        // coluna inicial pelo slug
        $coluna = KanbanColuna::where('slug', $data['status_inicial'])
            ->where('empresa_id', $user->empresa_id)
            ->first();

        $tarefa = Tarefa::create([
            'empresa_id'     => $user->empresa_id,
            'cliente_id'     => $data['cliente_id'],
            'servico_id'     => $data['servico_id'],
            'unidade_id'     => $data['unidade_id'] ?? null,
            'responsavel_id' => $user->id,
            'coluna_id'      => $coluna?->id,
            'titulo'         => $servico->nome, // pode mudar depois
            'descricao'      => $data['observacoes'] ?? null,
            'prioridade'     => $data['prioridade'],
            'data_agendada'  => $data['data'],
            'hora_agendada'  => $data['hora'],
            'data_prevista'  => $data['prazo_sla'] ?? $data['data'],
            'status'         => Tarefa::PENDENTE ?? 'pendente',
        ]);

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa criada para o cliente existente.');
    }

    /**
     * Cliente NOVO (manual)
     */
    public function storeNovoCliente(Request $r)
    {
        $user = Auth::user();

        $data = $r->validate([
            // cliente (passo 1)
            'razao_social'   => ['required','string','max:255'],
            'nome_fantasia'  => ['nullable','string','max:255'],
            'cnpj'           => ['nullable','string','max:18'],
            'telefone'       => ['nullable','string','max:30'],
            'email'          => ['nullable','email','max:255'],
            'unidade_id'     => ['required'], // ajuste para exists:unidades,id se usar tabela
            // passo 2
            'servico_id'     => ['required','exists:servicos,id'],
            'data'           => ['required','date'],
            'hora'           => ['required','string'],
            'prioridade'     => ['required', Rule::in(['baixa','media','alta'])],
            'prazo_sla'      => ['nullable','date'],
            'observacoes'    => ['nullable','string'],
            // passo 3
            'status_inicial' => ['required','string'],
        ]);

        // cria o cliente simples
        $cliente = Cliente::create([
            'empresa_id'    => $user->empresa_id,
            'razao_social'  => $data['razao_social'],
            'nome_fantasia' => $data['nome_fantasia'] ?: $data['razao_social'],
            'cnpj'          => $data['cnpj'] ?? null,
            'email'         => $data['email'] ?? null,
            'telefone'      => $data['telefone'] ?? null,
            // se tiver campos de endereço/unidade específicos, ajuste aqui
        ]);

        $servico = Servico::findOrFail($data['servico_id']);

        $coluna = KanbanColuna::where('slug', $data['status_inicial'])
            ->where('empresa_id', $user->empresa_id)
            ->first();

        $tarefa = Tarefa::create([
            'empresa_id'     => $user->empresa_id,
            'cliente_id'     => $cliente->id,
            'servico_id'     => $data['servico_id'],
            'unidade_id'     => $data['unidade_id'] ?? null,
            'responsavel_id' => $user->id,
            'coluna_id'      => $coluna?->id,
            'titulo'         => $servico->nome,
            'descricao'      => $data['observacoes'] ?? null,
            'prioridade'     => $data['prioridade'],
            'data_agendada'  => $data['data'],
            'hora_agendada'  => $data['hora'],
            'data_prevista'  => $data['prazo_sla'] ?? $data['data'],
            'status'         => Tarefa::PENDENTE ?? 'pendente',
        ]);

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Cliente e tarefa criados com sucesso.');
    }
}
