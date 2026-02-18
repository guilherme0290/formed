<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ServicoTempo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TempoTarefasController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;

        $validator = Validator::make($request->all(), [
            'tempos' => ['nullable', 'array'],
            'tempos.*' => ['nullable', 'string', 'max:16'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $tempos = $request->input('tempos', []);
            foreach ($tempos as $servicoId => $valor) {
                $minutos = $this->parseTempoParaMinutos($valor);
                if ($minutos === null) {
                    $validator->errors()->add(
                        "tempos.$servicoId",
                        'Use minutos (ex: 90) ou duração no formato HH:MM (ex: 01:30).'
                    );
                    continue;
                }

                if ($minutos < 0 || $minutos > 10080) {
                    $validator->errors()->add(
                        "tempos.$servicoId",
                        'O tempo deve ficar entre 0 e 10080 minutos.'
                    );
                }
            }
        });

        $data = $validator->validate();

        $tempos = $data['tempos'] ?? [];
        foreach ($tempos as $servicoId => $valor) {
            $servicoId = (int) $servicoId;
            $minutos = $this->parseTempoParaMinutos($valor) ?? 0;

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

    private function parseTempoParaMinutos(mixed $valor): ?int
    {
        $texto = trim((string) $valor);
        if ($texto === '') {
            return 0;
        }

        if (ctype_digit($texto)) {
            return (int) $texto;
        }

        if (preg_match('/^(\d{1,3}):([0-5]\d)$/', $texto, $partes)) {
            return ((int) $partes[1] * 60) + (int) $partes[2];
        }

        return null;
    }
}
