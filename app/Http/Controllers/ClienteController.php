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

        $empresaId = $r->user()->empresa_id ?? 1;

        $clientes = Cliente::query()
            ->with('userCliente')
            ->where('empresa_id', $empresaId)
            ->when($q, function ($w) use ($q) {
                $doc = preg_replace('/\D+/', '', $q);

                $w->where(function ($x) use ($q, $doc) {
                    $x->where('razao_social', 'like', "%{$q}%")
                        ->orWhere('nome_fantasia', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('telefone', 'like', "%{$q}%");

                    // Só filtra por CNPJ se tiver número na busca
                    if ($doc !== '') {
                        $x->orWhere('cnpj', 'like', "%{$doc}%");
                    }
                });
            })
            ->when($status !== 'todos', fn($w) => $w->where('ativo', $status === 'ativo'))
            ->orderBy('razao_social')
            ->paginate(15)
            ->withQueryString();

        $routePrefix = $this->routePrefix();

        return view('clientes.index', compact('clientes', 'q', 'status', 'routePrefix'));
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

        $routePrefix = $this->routePrefix();

        return view('clientes.edit', [
            'cliente'         => $cliente,
            'estados'         => $estados,
            'cidadesDoEstado' => collect(),
            'ufSelecionada'   => null,
            'modo'            => 'create',
            'routePrefix'     => $routePrefix,
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
        $data['ativo']      = true;


        try {
            Cliente::create($data);

            return redirect()
                ->route($this->routeName('index'))
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

        $routePrefix = $this->routePrefix();

        return view('clientes.show', compact('cliente', 'routePrefix'));
    }

    public function acessoForm(Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        $userExistente = \App\Models\User::where('cliente_id', $cliente->id)->first();
        $senhaSugerida = \Illuminate\Support\Str::password(10);

        $routePrefix = $this->routePrefix();

        return view('clientes.acesso', compact('cliente', 'userExistente', 'senhaSugerida', 'routePrefix'));
    }

    public function criarAcesso(Request $request, Cliente $cliente)
    {
        $email = $cliente->email;
        if (!$email) {
            return back()->with('erro', 'O cliente não possui e-mail cadastrado para criar o acesso.');
        }

        // evita duplicar usuário para o mesmo cliente
        $userExistente = \App\Models\User::where('cliente_id', $cliente->id)->first();
        if ($userExistente) {
            return back()->with('erro', 'Já existe um usuário vinculado a este cliente.');
        }

        // evita conflito de e-mail
        if (\App\Models\User::where('email', $request->input('email', $email))->exists()) {
            return back()->with('erro', 'Já existe um usuário com este e-mail. Use outro e-mail.');
        }

        $papelCliente = \App\Models\Papel::whereRaw('lower(nome) = ?', ['cliente'])->first();
        if (!$papelCliente) {
            return back()->with('erro', 'Papel Cliente não encontrado. Cadastre o papel antes de criar o acesso.');
        }

        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string','min:8'],
        ]);

        $senhaTemporaria = $data['password'];

        $user = \App\Models\User::create([
            'name'                 => $cliente->nome_fantasia ?: $cliente->razao_social ?: 'Cliente '.$cliente->id,
            'email'                => $data['email'],
            'password'             => $senhaTemporaria,
            'papel_id'             => $papelCliente->id,
            'empresa_id'           => $cliente->empresa_id,
            'cliente_id'           => $cliente->id,
            'must_change_password' => true,
            'is_active'            => true,
        ]);

        return redirect()->route($this->routeName('show'), $cliente)->with([
            'ok' => 'Usuário do cliente criado com sucesso. Solicite a troca de senha no primeiro login.',
            'acesso_cliente' => [
                'email' => $user->email,
                'senha' => $senhaTemporaria,
            ],
        ]);
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

        $routePrefix = $this->routePrefix();

        return view('clientes.edit', [
            'cliente'         => $cliente,
            'estados'         => $estados,
            'cidadesDoEstado' => $cidadesDoEstado,
            'ufSelecionada'   => $ufSelecionada,
            'modo'            => 'edit',
            'routePrefix'     => $routePrefix,
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
                ->route($this->routeName('index'))
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
                ->route($this->routeName('index'))
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
            ]
        );
    }


    protected function authorizeCliente(Cliente $cliente): void
    {
        $empresaId = auth()->user()->empresa_id ?? 1;

        if ($cliente->empresa_id != $empresaId) {
            abort(403);
        }
    }

    private function routePrefix(): string
    {
        return request()->routeIs('comercial.clientes.*')
            ? 'comercial.clientes'
            : 'clientes';
    }

    private function routeName(string $suffix): string
    {
        return $this->routePrefix() . '.' . $suffix;
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

    /**
     * >>> PORTAL DO CLIENTE <<<
     * Seleciona o cliente e guarda na sessão para usar no painel /cliente
     */
    public function selecionarParaPortal(Request $request, Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        $request->session()->put('portal_cliente_id', $cliente->id);

        $user = $request->user();
        if ($user) {
            $user->cliente_id = $cliente->id;
            $user->save();
        }

        return redirect()->route('clientes.dashboard');
    }


}
