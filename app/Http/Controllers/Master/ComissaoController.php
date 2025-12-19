<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\ServicoComissao;
use App\Models\Servico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComissaoController extends Controller
{
    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id ?? 1;

        $regras = ServicoComissao::query()
            ->where('empresa_id', $empresaId)
            ->with('servico:id,nome,ativo')
            ->orderBy('servico_id')
            ->orderByDesc('vigencia_inicio')
            ->get();

        $servicosComRegra = $regras->pluck('servico_id')->all();

        $servicos = Servico::query()
            ->where('empresa_id', $empresaId)
            ->where(function ($q) use ($servicosComRegra) {
                $q->where('ativo', true);

                if (!empty($servicosComRegra)) {
                    $q->orWhereIn('id', $servicosComRegra);
                }
            })
            ->orderBy('nome')
            ->get();

        $regrasPorServico = $regras->groupBy('servico_id');

        return view('master.comissoes.index', compact('servicos', 'regrasPorServico'));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;

        $data = $request->validate([
            'servico_id'      => ['required', 'exists:servicos,id'],
            'percentual'      => ['required', 'numeric', 'min:0', 'max:100'],
            'vigencia_inicio' => ['required', 'date'],
            'vigencia_fim'    => ['nullable', 'date', 'after_or_equal:vigencia_inicio'],
            'ativo'           => ['sometimes', 'boolean'],
        ]);

        // impede cadastrar regra de outra empresa
        $servico = Servico::where('empresa_id', $empresaId)
            ->findOrFail($data['servico_id']);

        ServicoComissao::create([
            'empresa_id'      => $empresaId,
            'servico_id'      => $servico->id,
            'percentual'      => $data['percentual'],
            'vigencia_inicio' => $data['vigencia_inicio'],
            'vigencia_fim'    => $data['vigencia_fim'] ?? null,
            'ativo'           => $request->boolean('ativo', true),
            'created_by'      => $request->user()->id ?? null,
        ]);

        return redirect()
            ->route('master.comissoes.index')
            ->with('ok', 'Regra cadastrada com sucesso.');
    }

    public function update(Request $request, ServicoComissao $servicoComissao): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->assertEmpresa($servicoComissao, $empresaId);

        $data = $request->validate([
            'percentual'      => ['required', 'numeric', 'min:0', 'max:100'],
            'vigencia_inicio' => ['required', 'date'],
            'vigencia_fim'    => ['nullable', 'date', 'after_or_equal:vigencia_inicio'],
            'ativo'           => ['sometimes', 'boolean'],
        ]);

        $servicoComissao->update([
            'percentual'      => $data['percentual'],
            'vigencia_inicio' => $data['vigencia_inicio'],
            'vigencia_fim'    => $data['vigencia_fim'] ?? null,
            'ativo'           => $request->boolean('ativo', false),
        ]);

        return redirect()
            ->route('master.comissoes.index')
            ->with('ok', 'Regra atualizada com sucesso.');
    }

    public function destroy(Request $request, ServicoComissao $servicoComissao): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $this->assertEmpresa($servicoComissao, $empresaId);

        $servicoComissao->delete();

        return redirect()
            ->route('master.comissoes.index')
            ->with('ok', 'Regra removida com sucesso.');
    }

    private function assertEmpresa(ServicoComissao $servicoComissao, int $empresaId): void
    {
        if ((int) $servicoComissao->empresa_id !== $empresaId) {
            abort(403);
        }
    }
}
