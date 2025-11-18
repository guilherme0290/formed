<?php

namespace App\Http\Controllers;

use App\Models\Cidade;
use App\Models\Cliente;
use App\Models\Estado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClienteController extends Controller
{
    /**
     * LISTAGEM DE CLIENTES
     */
    public function index(Request $r)
    {
        $q      = trim((string) $r->query('q', ''));
        $status = $r->query('status', 'todos'); // todos|ativo|inativo

        // Por enquanto: empresa fixa = 1 se usuário não tiver empresa
        $empresaId = $r->user()->empresa_id ?? 1;

        $clientes = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->when($q, function ($w) use ($q) {
                $doc = preg_replace('/\D+/', '', $q);

                $w->where(function ($x) use ($q, $doc) {
                    $x->where('razao_social', 'like', "%{$q}%")
                        ->orWhere('nome_fantasia', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('telefone', 'like', "%{$q}%")
                        ->orWhere('cnpj', 'like', "%{$doc}%");
                });
            })
            ->when($status !== 'todos', fn($w) => $w->where('ativo', $status === 'ativo'))
            ->orderBy('razao_social')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'q', 'status'));
    }

    /**
     * CONSULTA CNPJ VIA API
     * (caso você queira usar via AJAX depois)
     */
    public function consultaCnpj($cnpj)
    {
        $cnpjLimpo = preg_replace('/\D/', '', $cnpj);

        $resp = Http::get("https://www.receitaws.com.br/v1/cnpj/{$cnpjLimpo}");

        if ($resp->failed()) {
            return response()->json(['error' => 'Não foi possível consultar'], 500);
        }

        $dados = $resp->json();

        if (isset($dados['status']) && $dados['status'] === 'ERROR') {
            return response()->json(['error' => $dados['message'] ?? 'CNPJ não encontrado'], 404);
        }

        return response()->json([
            'razao_social'  => $dados['nome'] ?? null,
            'nome_fantasia' => $dados['fantasia'] ?? null,
            'cep'           => $dados['cep'] ?? null,
            'endereco'      => $dados['logradouro'] ?? null,
            'bairro'        => $dados['bairro'] ?? null,
            'complemento'   => $dados['complemento'] ?? null,
            'uf'            => $dados['uf'] ?? null,
            'municipio'     => $dados['municipio'] ?? null,
        ]);
    }

    /**
     * FORM DE CADASTRO
     */
    public function create()
    {
        $cliente = new Cliente();

        // se sua tabela tiver "uf", pode ajustar; aqui uso "sigla"
        $estados = Estado::orderBy('uf')->get(['uf', 'nome']);

        return view('clientes.edit', [
            'cliente'         => $cliente,
            'estados'         => $estados,
            'cidadesDoEstado' => collect(),
            'ufSelecionada'   => null,
            'modo'            => 'create',
        ]);
    }

    /**
     * SALVAR NOVO
     */
    public function store(Request $r)
    {
        $data = $this->validateData($r);

        $empresaId = $r->user()->empresa_id ?? 1;

        $data['empresa_id'] = $empresaId;
        $data['ativo']      = $r->boolean('ativo');




        try {
            Cliente::create($data);

            return redirect()
                ->route('clientes.index')
                ->with('ok', 'Cliente cadastrado com sucesso!');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('erro', 'Falha ao salvar o cliente. Tente novamente.');
        }
    }

    /**
     * SHOW / DETALHE
     */
    public function show(Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        return view('clientes.show', compact('cliente'));
    }

    /**
     * FORM DE EDIÇÃO
     */
    public function edit(Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        $estados = Estado::orderBy('uf')->get(['uf', 'nome']);

        // se não tiver cidade associada, isso aqui vira null sem quebrar
        $ufSelecionada = optional(optional($cliente->cidade)->estado)->uf;

        if ($ufSelecionada) {
            $estado = Estado::where('uf', $ufSelecionada)->first();
            $cidadesDoEstado = $estado
                ? $estado->cidades()->orderBy('nome')->get(['id', 'nome'])
                : collect();
        } else {
            $cidadesDoEstado = collect();
        }

        return view('clientes.edit', [
            'cliente'         => $cliente,
            'estados'         => $estados,
            'cidadesDoEstado' => $cidadesDoEstado,
            'ufSelecionada'   => $ufSelecionada,
            'modo'            => 'edit',
        ]);
    }

    /**
     * ATUALIZAR
     */
    public function update(Request $r, Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        $data = $this->validateData($r);

        $data['empresa_id'] = $cliente->empresa_id ?? ($r->user()->empresa_id ?? 1);
        $data['ativo']      = $r->boolean('ativo');



        try {
            $cliente->update($data);

            return redirect()
                ->route('clientes.index')
                ->with('ok', 'Cliente atualizado com sucesso!');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('erro', 'Falha ao atualizar o cliente. Tente novamente.');
        }
    }

    /**
     * EXCLUIR
     */
    public function destroy(Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        try {
            $cliente->delete();

            return redirect()
                ->route('clientes.index')
                ->with('ok', 'Cliente excluído com sucesso!');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('erro', 'Não foi possível excluir o cliente.');
        }
    }

    /**
     * VALIDAÇÃO DOS CAMPOS
     */
    protected function validateData(Request $r): array
    {
        return $r->validate(
            [
                'razao_social'   => ['required', 'string', 'max:255'],
                'nome_fantasia'  => ['nullable', 'string', 'max:255'],
                'cnpj'           => ['required', 'string', 'max:20'],
                'email'          => ['nullable', 'email', 'max:255'],
                'telefone'       => ['nullable', 'string', 'max:30'],
                'cep'            => ['nullable', 'string', 'max:20'],
                'endereco'       => ['nullable', 'string', 'max:255'],
                'numero'         => ['nullable', 'string', 'max:20'],
                'bairro'         => ['nullable', 'string', 'max:150'],
                'complemento'    => ['nullable', 'string', 'max:255'],
                'cidade_id'      => ['required', 'exists:cidades,id'],
            ],
            [
                // mensagens amigáveis 💬
                'razao_social.required' => 'Informe a razão social do cliente.',
                'razao_social.max'      => 'A razão social deve ter no máximo 255 caracteres.',

                'cnpj.required'         => 'Informe o CNPJ do cliente.',
                'cnpj.max'              => 'O CNPJ está muito longo. Confira o número digitado.',

                'email.email'           => 'Informe um e-mail válido (ex: nome@empresa.com).',

                'cidade_id.required'    => 'Selecione a cidade do cliente.',
                'cidade_id.exists'      => 'A cidade selecionada é inválida. Escolha uma cidade da lista.',
            ]
        );
    }

    /**
     * GARANTE QUE O CLIENTE É DA MESMA EMPRESA
     */
    protected function authorizeCliente(Cliente $cliente): void
    {
        $empresaId = auth()->user()->empresa_id ?? 1;

        if ($cliente->empresa_id != $empresaId) {
            abort(403);
        }
    }

    /**
     * CIDADES POR UF (API IBGE)
     * retorna JSON: [{id, nome}, ...]
     */
    public function cidadesPorUf(string $uf)
    {
        $uf = strtoupper($uf);

        // 1) Estado no banco
        $estado = Estado::where('uf', $uf)->first();

        if (! $estado) {
            return response()->json([]);
        }

        // 2) Cidades do banco
        $cidadesDb = Cidade::where('estado_id', $estado->id)->get(['id', 'nome']);

        // chave normalizada -> cidade_id
        $map = [];
        foreach ($cidadesDb as $c) {
            $nomeNormalizado = strtolower(
                preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $c->nome))
            );
            $map[$nomeNormalizado] = $c->id;
        }

        // 3) API IBGE
        $resp = Http::get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios");

        if (! $resp->successful()) {
            return response()->json([]);
        }

        $resultado = [];

        foreach ($resp->json() as $m) {

            $nomeApi = $m['nome'] ?? null;
            if (!$nomeApi) continue;

            $nomeApiNormalizado = strtolower(
                preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $nomeApi))
            );

            $cidadeId = $map[$nomeApiNormalizado] ?? null;

            if ($cidadeId) {
                $resultado[] = [
                    'id'   => $cidadeId,  // 👈 ID DO SEU BANCO!
                    'nome' => $nomeApi,
                ];
            }
        }

        return response()->json($resultado);
    }
}
