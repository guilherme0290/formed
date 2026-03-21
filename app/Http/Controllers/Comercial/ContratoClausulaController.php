<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ContratoClausula;
use App\Models\Servico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContratoClausulaController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id;
        $servico = strtoupper((string) $request->query('servico', ''));

        $query = ContratoClausula::query()->where('empresa_id', $empresaId);

        if ($servico !== '') {
            $query->where('servico_tipo', $servico);
        }

        $clausulas = $query->get();
        $roots = $clausulas
            ->whereNull('parent_id')
            ->sortBy(fn (ContratoClausula $c) => sprintf('%010d-%010d', (int) ($c->ordem_local ?? $c->ordem ?? 0), (int) $c->id))
            ->values();
        $childrenByParent = $clausulas
            ->whereNotNull('parent_id')
            ->groupBy('parent_id')
            ->map(fn ($group) => $group
                ->sortBy(fn (ContratoClausula $c) => sprintf('%010d-%010d', (int) ($c->ordem_local ?? $c->ordem ?? 0), (int) $c->id))
                ->values());

        $serviceTypes = $this->serviceTypeOptions($empresaId);

        return view('comercial.contratos.clausulas.index', [
            'roots' => $roots,
            'childrenByParent' => $childrenByParent,
            'servico' => $servico,
            'serviceTypes' => $serviceTypes,
        ]);
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
        $empresaId = (int) $request->user()->empresa_id;
        $data = $this->validateData($request, false, null, $empresaId);
        $data['empresa_id'] = $empresaId;

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

        $data = $this->validateData($request, true, $clausula, (int) $clausula->empresa_id);
        $clausula->update($data);

        return redirect()
            ->route('comercial.contratos.clausulas.index')
            ->with('ok', 'Cláusula atualizada com sucesso.');
    }

    public function show(ContratoClausula $clausula): JsonResponse
    {
        $this->authorizeClausula($clausula);

        return response()->json([
            'data' => [
                'id' => $clausula->id,
                'parent_id' => $clausula->parent_id,
                'servico_tipo' => $clausula->servico_tipo,
                'slug' => $clausula->slug,
                'titulo' => $clausula->titulo,
                'ordem_local' => $clausula->ordem_local ?? $clausula->ordem ?? 0,
                'ativo' => (bool) $clausula->ativo,
                'html_template' => (string) $clausula->html_template,
                'content_text' => $this->plainTextFromHtml((string) $clausula->html_template),
            ],
        ]);
    }

    public function destroy(ContratoClausula $clausula): RedirectResponse
    {
        $this->authorizeClausula($clausula);

        DB::transaction(function () use ($clausula) {
            ContratoClausula::query()
                ->where('empresa_id', $clausula->empresa_id)
                ->where('parent_id', $clausula->id)
                ->delete();

            $clausula->delete();
        });

        return redirect()
            ->route('comercial.contratos.clausulas.index')
            ->with('ok', 'Cláusula removida.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $empresaId = (int) $request->user()->empresa_id;
        $tree = $request->input('tree', []);

        if (!is_array($tree)) {
            throw ValidationException::withMessages([
                'tree' => 'Estrutura inválida para reordenação.',
            ]);
        }

        $allIds = [];
        foreach ($tree as $root) {
            $rootId = (int) ($root['id'] ?? 0);
            if ($rootId > 0) {
                $allIds[] = $rootId;
            }
            $children = $root['children'] ?? [];
            if (is_array($children)) {
                foreach ($children as $childId) {
                    $childId = (int) $childId;
                    if ($childId > 0) {
                        $allIds[] = $childId;
                    }
                }
            }
        }

        $allIds = array_values(array_unique($allIds));
        if (empty($allIds)) {
            return response()->json(['ok' => true]);
        }

        $clausulas = ContratoClausula::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('id', $allIds)
            ->get()
            ->keyBy('id');

        if ($clausulas->count() !== count($allIds)) {
            throw ValidationException::withMessages([
                'tree' => 'Existem cláusulas inválidas na estrutura enviada.',
            ]);
        }

        DB::transaction(function () use ($tree, $clausulas) {
            foreach (array_values($tree) as $rootIndex => $root) {
                $rootId = (int) ($root['id'] ?? 0);
                if ($rootId <= 0 || !$clausulas->has($rootId)) {
                    continue;
                }

                /** @var ContratoClausula $rootClause */
                $rootClause = $clausulas->get($rootId);
                $rootOrder = $rootIndex + 1;
                $rootClause->update([
                    'parent_id' => null,
                    'ordem_local' => $rootOrder,
                    'ordem' => $rootOrder,
                ]);

                $children = $root['children'] ?? [];
                if (!is_array($children)) {
                    continue;
                }

                foreach (array_values($children) as $childIndex => $childId) {
                    $childId = (int) $childId;
                    if ($childId <= 0 || !$clausulas->has($childId)) {
                        continue;
                    }

                    /** @var ContratoClausula $childClause */
                    $childClause = $clausulas->get($childId);
                    $childOrder = $childIndex + 1;
                    $childClause->update([
                        'parent_id' => $rootId,
                        'ordem_local' => $childOrder,
                        'ordem' => $childOrder,
                    ]);
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    private function authorizeClausula(ContratoClausula $clausula): void
    {
        abort_unless($clausula->empresa_id === auth()->user()->empresa_id, 403);
    }

    private function validateData(Request $request, bool $updating, ?ContratoClausula $clausula, int $empresaId): array
    {
        $slugUniqueRule = Rule::unique('contrato_clausulas', 'slug')
            ->where('empresa_id', $empresaId);

        if ($clausula) {
            $slugUniqueRule = $slugUniqueRule->ignore($clausula->id);
        }

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('contrato_clausulas', 'id')],
            'servico_tipo' => ['required', 'string', 'max:40'],
            'slug' => ['required', 'string', 'max:80', $slugUniqueRule],
            'titulo' => ['required', 'string', 'max:160'],
            'ordem_local' => ['nullable', 'integer', 'min:0'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'content_text' => ['nullable', 'string'],
            'html_template' => ['nullable', 'string'],
            'editar_html' => ['nullable', 'boolean'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $servicoTipo = strtoupper(trim((string) ($data['servico_tipo'] ?? 'GERAL')));
        $data['servico_tipo'] = $servicoTipo !== '' ? $servicoTipo : 'GERAL';
        $data['slug'] = trim((string) ($data['slug'] ?? ''));
        $data['ativo'] = $request->boolean('ativo');

        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;
        if ($parentId) {
            $parent = ContratoClausula::query()
                ->where('empresa_id', $empresaId)
                ->where('id', $parentId)
                ->first();

            if (!$parent) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Cláusula pai inválida.',
                ]);
            }
            if ($parent->parent_id !== null) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Subcláusula não pode ter subcláusulas.',
                ]);
            }
            if ($clausula && $parent->id === $clausula->id) {
                throw ValidationException::withMessages([
                    'parent_id' => 'A cláusula não pode ser filha dela mesma.',
                ]);
            }
        }
        $data['parent_id'] = $parentId;

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

        $ordemLocal = $data['ordem_local'] ?? $data['ordem'] ?? null;
        if ($ordemLocal === null) {
            $ordemLocal = $this->resolveNextOrder($empresaId, $parentId, $clausula?->id);
        }
        $data['ordem_local'] = max(0, (int) $ordemLocal);
        $data['ordem'] = $data['ordem_local'];

        $data['html_template'] = $this->normalizeClauseHtml($htmlTemplate);
        unset($data['content_text'], $data['editar_html']);

        return $data;
    }

    private function resolveNextOrder(int $empresaId, ?int $parentId, ?int $ignoreId): int
    {
        $query = ContratoClausula::query()
            ->where('empresa_id', $empresaId)
            ->where('parent_id', $parentId);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return (int) $query->max('ordem_local') + 1;
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
