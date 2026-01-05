<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Funcao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FuncoesController extends Controller
{
    private function autorizarAcesso(Request $request): void
    {
        $user = $request->user();

        if (!$user || !$user->hasPapel(['Comercial', 'Master'])) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $this->autorizarAcesso($request);

        $empresaId = $request->user()->empresa_id;
        $q = $request->string('q')->toString();
        $status = $request->string('status')->toString(); // ativos | inativos | ''

        $funcoes = Funcao::query()
            ->where('empresa_id', $empresaId)
            ->withCount(['funcionarios', 'gheFuncoes'])
            ->when($q !== '', fn($query) => $query->where('nome', 'like', "%{$q}%"))
            ->when($status === 'ativos', fn($query) => $query->where('ativo', true))
            ->when($status === 'inativos', fn($query) => $query->where('ativo', false))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('comercial.funcoes.index', [
            'funcoes' => $funcoes,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function store(Request $request)
    {
        $this->autorizarAcesso($request);

        $empresaId = $request->user()->empresa_id;

        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('funcoes', 'nome')->where('empresa_id', $empresaId),
            ],
            'cbo' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $data['empresa_id'] = $empresaId;
        $data['ativo'] = $request->boolean('ativo');

        Funcao::create($data);

        return back()->with('ok', 'Função cadastrada com sucesso.');
    }

    public function update(Request $request, Funcao $funcao)
    {
        $this->autorizarAcesso($request);

        $empresaId = $request->user()->empresa_id;
        abort_if($funcao->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('funcoes', 'nome')->where('empresa_id', $empresaId)->ignore($funcao->id),
            ],
            'cbo' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $data['ativo'] = $request->boolean('ativo');

        $funcao->update($data);

        return back()->with('ok', 'Função atualizada com sucesso.');
    }

    public function destroy(Request $request, Funcao $funcao)
    {
        $this->autorizarAcesso($request);

        $empresaId = $request->user()->empresa_id;
        abort_if($funcao->empresa_id !== $empresaId, 403);

        $temVinculo = $funcao->funcionarios()->exists()
            || $funcao->gheFuncoes()->exists();

        if ($temVinculo) {
            $funcao->update(['ativo' => false]);

            return back()->with('ok', 'Função possui vínculos e foi inativada.');
        }

        $funcao->delete();

        return back()->with('ok', 'Função excluída com sucesso.');
    }
}
