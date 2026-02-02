<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\DashboardPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardPreferenceController extends Controller
{
    private const ALLOWED_KEYS = [
        'faturamento-global',
        'servicos-consumidos',
        'financeiro-pendente',
        'financeiro-recebido',
        'clientes-ativos',
        'tempo-medio',
        'agendamentos-dia',
        'relatorios-master',
        'relatorios-avancados',
    ];

    public function show(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $preference = DashboardPreference::query()
            ->where('user_id', $userId)
            ->first();

        $defaults = array_fill_keys(self::ALLOWED_KEYS, true);
        $data = is_array($preference?->data) ? $preference->data : [];

        return response()->json([
            'visibility' => array_merge($defaults, $data),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visibility' => ['required', 'array'],
            'visibility.*' => ['boolean'],
        ]);

        $incoming = $validated['visibility'] ?? [];
        $filtered = array_intersect_key($incoming, array_flip(self::ALLOWED_KEYS));

        $data = [];
        foreach (self::ALLOWED_KEYS as $key) {
            if (array_key_exists($key, $filtered)) {
                $data[$key] = (bool) $filtered[$key];
            }
        }

        DashboardPreference::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['data' => $data]
        );

        return response()->json(['ok' => true]);
    }
}
