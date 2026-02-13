<?php

namespace App\Http\Controllers\Operacional\Concerns;

use App\Models\Tarefa;
use App\Models\Venda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ValidatesClientePortalTaskEditing
{
    protected function isClientePortalRequest(Request $request): bool
    {
        $origem = (string) $request->query('origem', $request->input('origem', ''));
        $user = $request->user();

        return $origem === 'cliente'
            || (is_object($user) && method_exists($user, 'isCliente') && $user->isCliente());
    }

    protected function ensureClientePodeEditarTarefa(Request $request, ?Tarefa $tarefa): ?RedirectResponse
    {
        if (!$this->isClientePortalRequest($request) || !$tarefa) {
            return null;
        }

        $slug = mb_strtolower((string) optional($tarefa->coluna)->slug);
        if ($slug !== 'pendente') {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('erro', 'Você só pode editar serviços que estejam em Pendente.');
        }

        $temVinculoFinanceiro = Venda::query()
            ->where('tarefa_id', $tarefa->id)
            ->exists();

        if ($temVinculoFinanceiro) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('erro', 'Este serviço já possui vínculo financeiro e não pode ser editado pelo portal.');
        }

        return null;
    }
}

