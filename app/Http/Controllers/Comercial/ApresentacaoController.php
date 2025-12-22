<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Proposta;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ApresentacaoController extends Controller
{
    private const SESSION_KEY = 'apresentacao_proposta';

    private const SEGMENTOS = [
        'construcao-civil' => 'Construção Civil',
        'industria' => 'Indústria',
        'comercio' => 'Comércio / Varejo',
        'restaurante' => 'Restaurante / Alimentação',
    ];

    public function cliente(Request $request)
    {
        $empresaId = $request->user()->empresa_id;

        $propostas = Proposta::query()
            ->with('cliente')
            ->where('empresa_id', $empresaId)
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'cliente_id', 'codigo', 'status', 'created_at', 'valor_total']);

        $draft = $request->session()->get(self::SESSION_KEY . '.cliente', []);

        return view('comercial.apresentacao.cliente', [
            'propostas' => $propostas,
            'draft' => $draft,
        ]);
    }

    public function clienteStore(Request $request)
    {
        $data = $request->validate([
            'proposta_id' => ['nullable', 'integer'],
            'cnpj' => ['required', 'string', 'max:30'],
            'razao_social' => ['required', 'string', 'max:255'],
            'contato' => ['required', 'string', 'max:120'],
            'telefone' => ['required', 'string', 'max:30'],
        ]);

        $empresaId = $request->user()->empresa_id;
        if (!empty($data['proposta_id'])) {
            $ok = Proposta::where('id', $data['proposta_id'])
                ->where('empresa_id', $empresaId)
                ->exists();
            abort_if(!$ok, 403);
        }

        $request->session()->put(self::SESSION_KEY . '.cliente', [
            'proposta_id' => $data['proposta_id'] ?? null,
            'cnpj' => $data['cnpj'],
            'razao_social' => $data['razao_social'],
            'contato' => $data['contato'],
            'telefone' => $data['telefone'],
        ]);

        return redirect()->route('comercial.apresentacao.segmento');
    }

    public function segmento(Request $request)
    {
        $cliente = $request->session()->get(self::SESSION_KEY . '.cliente');
        if (!$cliente) {
            return redirect()->route('comercial.apresentacao.cliente');
        }

        return view('comercial.apresentacao.segmento', [
            'cliente' => $cliente,
            'segmentos' => self::SEGMENTOS,
        ]);
    }

    public function show(Request $request, string $segmento)
    {
        $cliente = $request->session()->get(self::SESSION_KEY . '.cliente');
        if (!$cliente) {
            return redirect()->route('comercial.apresentacao.cliente');
        }

        abort_unless(array_key_exists($segmento, self::SEGMENTOS), 404);

        $request->session()->put(self::SESSION_KEY . '.segmento', $segmento);

        return view('comercial.apresentacao.show', [
            'cliente' => $cliente,
            'segmento' => $segmento,
            'segmentoNome' => self::SEGMENTOS[$segmento],
        ]);
    }

    public function pdf(Request $request, string $segmento)
    {
        $cliente = $request->session()->get(self::SESSION_KEY . '.cliente');
        if (!$cliente) {
            return redirect()->route('comercial.apresentacao.cliente');
        }

        abort_unless(array_key_exists($segmento, self::SEGMENTOS), 404);

        $logoPath = public_path('storage/logo.svg');
        $logoData = is_file($logoPath)
            ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('comercial.apresentacao.pdf', [
            'cliente' => $cliente,
            'segmento' => $segmento,
            'segmentoNome' => self::SEGMENTOS[$segmento],
            'logoData' => $logoData,
        ])->setPaper('a4');

        return $pdf->stream('apresentacao-' . $segmento . '.pdf');
    }

    public function cancelar(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);
        return redirect()->route('comercial.dashboard');
    }
}
