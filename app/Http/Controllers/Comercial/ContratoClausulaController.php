<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\ContratoClausula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContratoClausulaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        $query = ContratoClausula::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('ordem')
            ->orderBy('id');

        $servico = $request->get('servico');
        if ($servico) {
            $query->where('servico_tipo', strtoupper((string) $servico));
        }

        $clausulas = $query->paginate(20)->withQueryString();

        return view('comercial.contratos.clausulas.index', compact('clausulas', 'servico'));
    }

    public function create(Request $request): View
    {
        return view('comercial.contratos.clausulas.form', [
            'clausula' => new ContratoClausula(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        $data = $this->validateData($request);
        $data['empresa_id'] = $empresaId;

        ContratoClausula::create($data);

        return redirect()
            ->route('comercial.contratos.clausulas.index')
            ->with('ok', 'Cláusula criada com sucesso.');
    }

    public function edit(ContratoClausula $clausula): View
    {
        $user = auth()->user();
        abort_unless($clausula->empresa_id === $user->empresa_id, 403);

        return view('comercial.contratos.clausulas.form', compact('clausula'));
    }

    public function update(Request $request, ContratoClausula $clausula): RedirectResponse
    {
        $user = $request->user();
        abort_unless($clausula->empresa_id === $user->empresa_id, 403);

        $data = $this->validateData($request);
        $clausula->update($data);

        return redirect()
            ->route('comercial.contratos.clausulas.index')
            ->with('ok', 'Cláusula atualizada com sucesso.');
    }

    public function destroy(ContratoClausula $clausula): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($clausula->empresa_id === $user->empresa_id, 403);

        $clausula->delete();

        return redirect()
            ->route('comercial.contratos.clausulas.index')
            ->with('ok', 'Cláusula removida.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'servico_tipo' => ['required', 'string', 'max:40'],
            'slug' => ['required', 'string', 'max:80'],
            'titulo' => ['required', 'string', 'max:160'],
            'ordem' => ['nullable', 'integer', 'min:0'],
            'html_template' => ['required', 'string'],
            'ativo' => ['nullable', 'boolean'],
        ]);
    }
}
