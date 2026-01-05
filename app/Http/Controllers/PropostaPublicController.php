<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use App\Services\AsoGheService;
use App\Services\PropostaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropostaPublicController extends Controller
{
    public function show(string $token)
    {
        $proposta = Proposta::where('public_token', $token)
            ->with(['cliente', 'empresa', 'vendedor', 'itens'])
            ->firstOrFail();

        $gheSnapshot = [];
        if ($proposta->cliente_id) {
            $gheSnapshot = app(AsoGheService::class)
                ->buildSnapshotForCliente($proposta->cliente_id, $proposta->empresa_id);
        }

        return view('public.propostas.show', [
            'proposta' => $proposta,
            'gheSnapshot' => $gheSnapshot,
        ]);
    }

    public function responder(Request $request, string $token, PropostaService $service)
    {
        $proposta = Proposta::where('public_token', $token)
            ->with(['cliente', 'empresa', 'vendedor', 'itens'])
            ->firstOrFail();

        $data = $request->validate([
            'acao' => ['required', 'string', 'in:aceitar,recusar'],
        ]);

        $statusAtual = strtoupper((string) $proposta->status);
        if (in_array($statusAtual, ['FECHADA', 'CANCELADA'], true)) {
            return back()->with('erro', 'Esta proposta já foi encerrada.');
        }

        if ($data['acao'] === 'aceitar') {
            try {
                $service->fechar($proposta->id, $proposta->vendedor_id ?? 0);
                $proposta->refresh();
                $proposta->update(['public_responded_at' => now()]);
                return back()->with('ok', 'Proposta aceita. Obrigado!');
            } catch (\Throwable $e) {
                report($e);
                $message = $e->getMessage() ?: 'Falha ao aceitar proposta.';
                if (method_exists($e, 'errors')) {
                    $message = collect($e->errors())->flatten()->first() ?? $message;
                }
                return back()->with('erro', $message);
            }
        }

        DB::transaction(function () use ($proposta) {
            $proposta->update([
                'status' => 'CANCELADA',
                'pipeline_status' => 'PERDIDO',
                'perdido_motivo' => 'Recusada pelo cliente',
                'public_responded_at' => now(),
            ]);
        });

        return back()->with('ok', 'Proposta recusada. Obrigado pelo retorno.');
    }
}
