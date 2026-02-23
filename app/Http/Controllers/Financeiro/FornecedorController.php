<?php

namespace App\Http\Controllers\Financeiro;

use App\Http\Controllers\Controller;
use App\Models\ContaPagar;
use App\Models\Fornecedor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FornecedorController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $user = $request->user();
            if (!$user || (!$user->hasPapel('Master') && !$user->hasPapel('Financeiro'))) {
                abort(403);
            }
            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $empresaId = $request->user()->empresa_id;
        $busca = trim((string) $request->input('busca', ''));
        $modal = (string) $request->input('modal', '');
        $fornecedorEdicaoId = $request->input('fornecedor');

        $fornecedores = Fornecedor::query()
            ->where('empresa_id', $empresaId)
            ->when($busca !== '', function ($query) use ($busca) {
                $query->where(function ($q) use ($busca) {
                    $q->where('razao_social', 'like', '%' . $busca . '%')
                        ->orWhere('nome_fantasia', 'like', '%' . $busca . '%')
                        ->orWhere('cpf_cnpj', 'like', '%' . $busca . '%');
                });
            })
            ->orderBy('razao_social')
            ->paginate(12)
            ->withQueryString();

        $fornecedorEdicao = null;
        if ($modal === 'edit' && is_numeric($fornecedorEdicaoId)) {
            $fornecedorEdicao = Fornecedor::query()
                ->where('empresa_id', $empresaId)
                ->whereKey((int) $fornecedorEdicaoId)
                ->first();
        }

        $modalAberto = in_array($modal, ['create', 'edit'], true)
            || (bool) old('fornecedor_modal_context')
            || $request->session()->has('errors');

        $modalContexto = old('fornecedor_modal_context');
        if ($modalContexto === 'create' || $modalContexto === 'edit') {
            $modal = $modalContexto;
        }
        if ($modal === '' && $modalAberto) {
            $modal = 'create';
        }

        if (!$fornecedorEdicao && $modal === 'edit') {
            $oldEdicaoId = old('fornecedor_modal_edit_id');
            if (is_numeric($oldEdicaoId)) {
                $fornecedorEdicao = Fornecedor::query()
                    ->where('empresa_id', $empresaId)
                    ->whereKey((int) $oldEdicaoId)
                    ->first();
            }
        }

        if ($modal === 'edit' && !$fornecedorEdicao) {
            $modal = 'create';
        }

        return view('financeiro.fornecedores.index', [
            'fornecedores' => $fornecedores,
            'filtros' => [
                'busca' => $busca,
            ],
            'modalFornecedor' => [
                'aberto' => $modalAberto,
                'modo' => $modal,
                'fornecedorEdicao' => $fornecedorEdicao,
            ],
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('financeiro.fornecedores.index', array_filter([
            'busca' => $request->input('busca'),
            'modal' => 'create',
        ], fn ($value) => $value !== null && $value !== ''));
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id;
        $data = $this->validateData($request, $empresaId);

        Fornecedor::create($data);

        return $this->redirectPosAcao($request)
            ->with('success', 'Fornecedor cadastrado com sucesso.');
    }

    public function edit(Request $request, Fornecedor $fornecedor): RedirectResponse
    {
        $this->autorizarFornecedor($request, $fornecedor);

        return redirect()->route('financeiro.fornecedores.index', array_filter([
            'busca' => $request->input('busca'),
            'modal' => 'edit',
            'fornecedor' => $fornecedor->id,
        ], fn ($value) => $value !== null && $value !== ''));
    }

    public function update(Request $request, Fornecedor $fornecedor): RedirectResponse
    {
        $this->autorizarFornecedor($request, $fornecedor);

        $empresaId = $request->user()->empresa_id;
        $data = $this->validateData($request, $empresaId, $fornecedor->id);
        $fornecedor->update($data);

        return $this->redirectPosAcao($request)
            ->with('success', 'Fornecedor atualizado com sucesso.');
    }

    public function destroy(Request $request, Fornecedor $fornecedor): RedirectResponse
    {
        $this->autorizarFornecedor($request, $fornecedor);

        $possuiContas = ContaPagar::query()
            ->where('fornecedor_id', $fornecedor->id)
            ->exists();

        if ($possuiContas) {
            return $this->redirectPosAcao($request)->with('error', 'Não é possível excluir: fornecedor já possui contas a pagar vinculadas.');
        }

        $fornecedor->delete();

        return $this->redirectPosAcao($request)->with('success', 'Fornecedor excluído com sucesso.');
    }

    private function autorizarFornecedor(Request $request, Fornecedor $fornecedor): void
    {
        if ($fornecedor->empresa_id !== ($request->user()->empresa_id ?? null)) {
            abort(403);
        }
    }

    private function validateData(Request $request, int $empresaId, ?int $fornecedorId = null): array
    {
        $cpfCnpj = preg_replace('/\D+/', '', (string) $request->input('cpf_cnpj', ''));
        if ($cpfCnpj === '') {
            $cpfCnpj = null;
        }

        $request->merge([
            'cpf_cnpj' => $cpfCnpj,
        ]);

        $data = $request->validate([
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'tipo_pessoa' => ['required', 'string', 'in:PF,PJ'],
            'cpf_cnpj' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('fornecedores', 'cpf_cnpj')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                    ->ignore($fornecedorId),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'contato_nome' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:10'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'string', 'size:2'],
            'ativo' => ['nullable', 'boolean'],
        ]);

        $data['empresa_id'] = $empresaId;
        $data['cpf_cnpj'] = $cpfCnpj;
        $data['uf'] = isset($data['uf']) ? strtoupper((string) $data['uf']) : null;
        $data['ativo'] = (bool) ($data['ativo'] ?? false);

        return $data;
    }

    private function redirectPosAcao(Request $request): RedirectResponse
    {
        $returnUrl = trim((string) $request->input('_return_url', ''));
        if ($returnUrl !== '') {
            $appUrl = rtrim((string) config('app.url'), '/');
            if (str_starts_with($returnUrl, '/')) {
                return redirect($returnUrl);
            }
            if ($appUrl !== '' && str_starts_with($returnUrl, $appUrl)) {
                return redirect($returnUrl);
            }
        }

        return redirect()->route('financeiro.fornecedores.index');
    }
}
