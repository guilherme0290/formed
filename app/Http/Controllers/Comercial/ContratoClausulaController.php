<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ContratoClausula;
use App\Models\Servico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContratoClausulaController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id;
        $servico = strtoupper((string) $request->query('servico', ''));

        $query = ContratoClausula::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('ordem')
            ->orderBy('id');

        if ($servico !== '') {
            $query->where('servico_tipo', $servico);
        }

        $clausulas = $query->paginate(20)->withQueryString();

        $serviceTypes = $this->serviceTypeOptions($empresaId);

        return view('comercial.contratos.clausulas.index', compact('clausulas', 'servico', 'serviceTypes'));
    }

    public function create(Request $request): View
    {
        $empresaId = $request->user()->empresa_id;
        $serviceTypes = $this->serviceTypeOptions($empresaId);

        return view('comercial.contratos.clausulas.form', [
            'clausula' => new ContratoClausula(),
            'serviceTypes' => $serviceTypes,
            'contentText' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request, false);
        $data['empresa_id'] = $request->user()->empresa_id;

        ContratoClausula::create($data);

        return redirect()
            ->route('comercial.contratos.clausulas.index')
            ->with('ok', 'Cláusula criada com sucesso.');
    }

    public function edit(ContratoClausula $clausula): View
    {
        $this->authorizeClausula($clausula);
        $serviceTypes = $this->serviceTypeOptions((int) $clausula->empresa_id);

        return view('comercial.contratos.clausulas.form', [
            'clausula' => $clausula,
            'serviceTypes' => $serviceTypes,
            'contentText' => $this->plainTextFromHtml($clausula->html_template),
        ]);
    }

    public function update(Request $request, ContratoClausula $clausula): RedirectResponse
    {
        $this->authorizeClausula($clausula);

        $clausula->update($this->validateData($request, true));

        return redirect()
            ->route('comercial.contratos.clausulas.index')
            ->with('ok', 'Cláusula atualizada com sucesso.');
    }

    public function destroy(ContratoClausula $clausula): RedirectResponse
    {
        $this->authorizeClausula($clausula);

        $clausula->delete();

        return redirect()
            ->route('comercial.contratos.clausulas.index')
            ->with('ok', 'Cláusula removida.');
    }

    private function authorizeClausula(ContratoClausula $clausula): void
    {
        abort_unless($clausula->empresa_id === auth()->user()->empresa_id, 403);
    }

    private function validateData(Request $request, bool $updating): array
    {
        $data = $request->validate([
            'servico_tipo' => ['required', 'string', 'max:40'],
            'slug' => ['required', 'string', 'max:80'],
            'titulo' => ['required', 'string', 'max:160'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'content_text' => ['nullable', 'string'],
            'html_template' => ['nullable', 'string'],
            'editar_html' => ['nullable', 'boolean'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $servicoTipo = strtoupper(trim((string) ($data['servico_tipo'] ?? 'GERAL')));
        $data['servico_tipo'] = $servicoTipo !== '' ? $servicoTipo : 'GERAL';

        $editarHtml = (bool) ($request->boolean('editar_html'));
        $htmlTemplate = '';

        if ($editarHtml) {
            $htmlTemplate = trim((string) ($data['html_template'] ?? ''));
        }

        if ($htmlTemplate === '') {
            $contentText = trim((string) ($data['content_text'] ?? ''));
            $htmlTemplate = $this->buildHtmlFromText($contentText);
        }

        if ($htmlTemplate === '' && $updating) {
            $htmlTemplate = $this->buildHtmlFromText('');
        }

        $data['html_template'] = $this->normalizeClauseHtml($htmlTemplate);
        unset($data['content_text'], $data['editar_html']);

        return $data;
    }

    private function buildHtmlFromText(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn ($line) => $line !== ''));

        if (empty($lines)) {
            return '<p>Conteúdo da cláusula.</p>';
        }

        $html = '';
        foreach ($lines as $line) {
            $html .= '<p>' . e($line) . '</p>';
        }

        return $html;
    }

    private function plainTextFromHtml(string $html): string
    {
        $withoutHeading = $this->normalizeClauseHtml($html);
        $text = strip_tags($withoutHeading);
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        return trim($text);
    }

    private function normalizeClauseHtml(string $html): string
    {
        $normalized = trim($html);
        if ($normalized === '') {
            return '<p>Conteúdo da cláusula.</p>';
        }

        // Título é controlado pelo campo "titulo" da cláusula, não pelo conteúdo HTML.
        $normalized = preg_replace('/^\s*<h3\b[^>]*>.*?<\/h3>\s*/is', '', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function serviceTypeOptions(int $empresaId): array
    {
        $default = [
            'GERAL' => 'GERAL (todos os serviços)',
            'ASO' => 'ASO',
            'PCMSO' => 'PCMSO',
            'PGR' => 'PGR',
            'LTCAT' => 'LTCAT',
            'ESOCIAL' => 'ESOCIAL',
            'TREINAMENTO' => 'TREINAMENTO',
            'APR' => 'APR',
        ];

        $servicos = Servico::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->pluck('nome');

        foreach ($servicos as $nome) {
            $key = strtoupper(trim((string) $nome));
            if ($key === '' || isset($default[$key])) {
                continue;
            }
            $default[$key] = $key;
        }

        return $default;
    }
}
