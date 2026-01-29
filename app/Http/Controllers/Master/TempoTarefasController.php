<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ServicoTempo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TempoTarefasController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;

        $data = $request->validate([
            'tempos' => ['nullable', 'array'],
            'tempos.*' => ['nullable', 'integer', 'min:0', 'max:10080'],
        ]);

        $tempos = $data['tempos'] ?? [];
        foreach ($tempos as $servicoId => $minutos) {
            $servicoId = (int) $servicoId;
            $minutos = (int) $minutos;

            if ($minutos <= 0) {
                ServicoTempo::query()
                    ->where('empresa_id', $empresaId)
                    ->where('servico_id', $servicoId)
                    ->update([
                        'tempo_minutos' => 0,
                        'ativo' => false,
                    ]);
                continue;
            }

            ServicoTempo::updateOrCreate([
                'empresa_id' => $empresaId,
                'servico_id' => $servicoId,
            ], [
                'tempo_minutos' => $minutos,
                'ativo' => true,
            ]);
        }

        return redirect()
            ->route('master.email-caixas.index', ['tab' => 'tempos'])
            ->with('ok', 'Tempos das tarefas atualizados com sucesso.');
    }
}
