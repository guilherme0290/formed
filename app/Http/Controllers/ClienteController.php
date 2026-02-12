<?php

namespace App\Http\Controllers;

use App\Models\Cidade;
use App\Models\Cliente;
use App\Models\Estado;
use App\Models\Funcao;
use App\Models\ParametroCliente;
use App\Models\ParametroClienteAsoGrupo;
use App\Models\Servico;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * LISTAGEM DE CLIENTES
     */
    public function index(Request $r)
    {
        $q      = trim((string) $r->query('q', ''));
        $status = $r->query('status', 'todos'); // todos|ativo|inativo
        $qText  = trim(preg_replace('/\d+/', ' ', $q));
        $qText  = preg_replace('/\s+/', ' ', $qText);
        $doc    = preg_replace('/\D+/', '', $q);

        $empresaId = $r->user()->empresa_id ?? 1;

        $clientes = Cliente::query()
            ->with('userCliente')
            ->where('empresa_id', $empresaId)
            ->when($qText !== '' || $doc !== '', function ($w) use ($qText, $doc) {
                $w->where(function ($x) use ($qText, $doc) {
                    if ($qText !== '') {
                        $x->where('razao_social', 'like', "%{$qText}%")
                            ->orWhere('nome_fantasia', 'like', "%{$qText}%")
                            ->orWhere('email', 'like', "%{$qText}%")
                            ->orWhere('telefone', 'like', "%{$qText}%");
                    }

                    // Só filtra por CNPJ se tiver número na busca
                    if ($doc !== '') {
                        $x->orWhere('cnpj', 'like', "%{$doc}%")
                            ->orWhere('telefone', 'like', "%{$doc}%");
                    }
                });
            })
            ->when($status !== 'todos', fn($w) => $w->where('ativo', $status === 'ativo'))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $routePrefix = $this->routePrefix();

        $autocompleteOptions = $clientes->getCollection()
            ->pluck('razao_social')
            ->filter()
            ->unique()
            ->values();

        return view('clientes.index', compact('clientes', 'q', 'status', 'routePrefix', 'autocompleteOptions'));
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
            'contato'       => $dados['fantasia'] ?? null,
            'telefone'      => $dados['telefone'] ?? $dados['telefone1'] ?? $dados['telefone2'] ?? null,
            'cep'           => $dados['cep'] ?? null,
            'endereco'      => $dados['logradouro'] ?? null,
            'bairro'        => $dados['bairro'] ?? null,
            'complemento'   => $dados['complemento'] ?? null,
            'uf'            => $dados['uf'] ?? null,
            'municipio'     => $dados['municipio'] ?? null,
        ]);
    }

    public function cnpjExists(Request $request, string $cnpj)
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $cnpjLimpo = preg_replace('/\D+/', '', (string) $cnpj);
        $ignorarId = $request->query('ignore') ? (int) $request->query('ignore') : null;

        if ($cnpjLimpo === '') {
            return response()->json(['exists' => false]);
        }

        $query = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->whereRaw("REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '-', ''), '/', '') = ?", [$cnpjLimpo]);

        if ($ignorarId) {
            $query->where('id', '!=', $ignorarId);
        }

        return response()->json([
            'exists' => $query->exists(),
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
        $data['vendedor_id'] = $r->user()?->id;


        try {
            $cliente = Cliente::create($data);
            $afterAction = $r->input('after_action');

            if ($afterAction === 'proposta') {
                return redirect()
                    ->route('comercial.propostas.create', ['cliente_id' => $cliente->id])
                    ->with('ok', 'Cliente cadastrado com sucesso!');
            }

            if ($afterAction === 'apresentacao') {
                $r->session()->put('apresentacao_proposta.cliente', [
                    'proposta_id' => null,
                    'cnpj' => $cliente->cnpj,
                    'razao_social' => $cliente->razao_social,
                    'contato' => $cliente->nome_fantasia ?: $cliente->razao_social,
                    'telefone' => $cliente->telefone,
                ]);

                return redirect()
                    ->route('comercial.apresentacao.cliente')
                    ->with('ok', 'Cliente cadastrado com sucesso!');
            }

            return redirect()
                ->route($this->routeName('edit'), $cliente)
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
        try {
            $loginTipo = $request->input('login_tipo', 'documento');
            if (!in_array($loginTipo, ['documento', 'email'], true)) {
                $loginTipo = 'documento';
            }

            $data = $request->validate([
                'login_tipo' => ['required', 'in:documento,email'],
                'documento' => [
                    $loginTipo === 'documento' ? 'required' : 'nullable',
                    'string',
                    function ($attribute, $value, $fail) use ($loginTipo) {
                        if ($loginTipo !== 'documento') {
                            return;
                        }
                        $doc = preg_replace('/\D+/', '', (string) $value);
                        if (strlen($doc) !== 14) {
                            $fail('Informe um CNPJ (14 dígitos) válido.');
                        }
                    },
                ],
                'email' => [
                    $loginTipo === 'email' ? 'required' : 'nullable',
                    'email',
                    'max:255',
                ],
                'password' => ['required','string','min:8'],
            ]);

            $documento = null;
            $email = null;

            if ($loginTipo === 'documento') {
                $documento = preg_replace('/\D+/', '', (string) ($data['documento'] ?? ''));
            }

            if ($loginTipo === 'email') {
                $email = $data['email'] ?? null;
            }
            if (is_string($email)) {
                $email = trim($email);
            }
            if ($email === '') {
                $email = null;
            }

            // evita duplicar usuário para o mesmo cliente
            $userExistente = \App\Models\User::where('cliente_id', $cliente->id)->first();
            if ($userExistente) {
                return back()->with('erro', 'Já existe um usuário vinculado a este cliente.');
            }

            // evita conflito de documento
            if ($documento && \App\Models\User::where('documento', $documento)->exists()) {
                return back()->with('erro', 'Já existe um usuário com este CPF/CNPJ. Use outro documento.');
            }

            // evita conflito de e-mail (se informado)
            if ($email && \App\Models\User::where('email', $email)->exists()) {
                return back()->with('erro', 'Já existe um usuário com este e-mail. Use outro e-mail.');
            }

            $papelCliente = \App\Models\Papel::whereRaw('lower(nome) = ?', ['cliente'])->first();
            if (!$papelCliente) {
                return back()->with('erro', 'Papel Cliente não encontrado. Cadastre o papel antes de criar o acesso.');
            }

            $senhaTemporaria = $data['password'];

            $user = \App\Models\User::create([
                'name'                 => $cliente->nome_fantasia ?: $cliente->razao_social ?: 'Cliente '.$cliente->id,
                'email'                => $email,
                'documento'            => $documento,
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
                    'email' => $user->email ?: $user->documento,
                    'senha' => $senhaTemporaria,
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->with('erro', 'Falha ao criar o acesso do cliente. Verifique os dados e tente novamente.');
        }
    }

    /**
     * FORM DE EDIÇÃO
     */
    public function edit(Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        $estados = Estado::orderBy('uf')->get(['uf', 'nome']);
        $empresaId = $cliente->empresa_id ?? (auth()->user()->empresa_id ?? 1);

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

        $esocialId = config('services.esocial_id');
        $servicos = Servico::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->when($esocialId, fn($q) => $q->where('id', '!=', $esocialId))
            ->orderBy('nome')
            ->get();
        $funcoes = Funcao::where('empresa_id', $empresaId)->orderBy('nome')->get(['id', 'nome']);

        $treinamentos = collect();
        $treinamentoId = (int) (config('services.treinamento_id') ?? 0);
        $padrao = TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->first();
        if ($padrao && $treinamentoId > 0) {
            $treinamentos = TabelaPrecoItem::query()
                ->where('tabela_preco_padrao_id', $padrao->id)
                ->where('servico_id', $treinamentoId)
                ->where('ativo', true)
                ->whereNotNull('codigo')
                ->orderBy('codigo')
                ->selectRaw('id, codigo, descricao as titulo')
                ->get();
        }

        $formasPagamento = [
            'Pix',
            'Boleto',
            'Cartão de crédito',
            'Cartão de débito',
            'Transferência',
        ];

        $parametro = ParametroCliente::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->latest('id')
            ->first();

        if ($parametro) {
            $parametro->load('itens');
        }

        $parametroAsoGrupos = $parametro
            ? ParametroClienteAsoGrupo::query()
                ->where('parametro_cliente_id', $parametro->id)
                ->with(['grupo', 'clienteGhe'])
                ->get()
            : collect();

        $vendedores = User::query()
            ->where('empresa_id', $empresaId)
            ->whereHas('papel', function ($q) {
                $q->whereIn('nome', ['Master', 'Comercial']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('clientes.edit', [
            'cliente'         => $cliente,
            'estados'         => $estados,
            'cidadesDoEstado' => $cidadesDoEstado,
            'ufSelecionada'   => $ufSelecionada,
            'modo'            => 'edit',
            'routePrefix'     => $routePrefix,
            'servicos'        => $servicos,
            'funcoes'         => $funcoes,
            'treinamentos'    => $treinamentos,
            'formasPagamento' => $formasPagamento,
            'parametro'       => $parametro,
            'parametroAsoGrupos' => $parametroAsoGrupos,
            'vendedores'      => $vendedores,
        ]);
    }

    /**
     * ATUALIZAR
     */
    public function update(Request $r, Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        if ($r->boolean('update_vendedor')) {
            $empresaId = $cliente->empresa_id ?? ($r->user()->empresa_id ?? 1);
            $data = $r->validate([
                'vendedor_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where(function ($q) use ($empresaId) {
                        $q->where('empresa_id', $empresaId);
                    }),
                    function ($attribute, $value, $fail) {
                        $user = User::with('papel')->find($value);
                        if (!$user || !$user->hasPapel(['Master', 'Comercial'])) {
                            $fail('Selecione um vendedor com perfil Master ou Comercial.');
                        }
                    },
                ],
            ]);

            $cliente->update(['vendedor_id' => $data['vendedor_id']]);

            return redirect()
                ->route($this->routeName('edit'), $cliente)
                ->with('ok', 'Vendedor atualizado com sucesso.');
        }

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
        $empresaId = $r->user()->empresa_id ?? 1;
        $clienteRoute = $r->route('cliente');
        $clienteId = $clienteRoute instanceof Cliente ? $clienteRoute->id : (is_numeric($clienteRoute) ? (int) $clienteRoute : null);

        return $r->validate(
            [
                'razao_social'   => ['required', 'string', 'max:255'],
                'nome_fantasia'  => ['nullable', 'string', 'max:255'],
                'cnpj'           => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('clientes', 'cnpj')
                        ->where(fn ($q) => $q->where('empresa_id', $empresaId))
                        ->ignore($clienteId),
                ],
                'email'          => ['nullable', 'email', 'max:255'],
                'telefone'       => ['nullable', 'string', 'max:30'],
                'contato'        => ['nullable', 'string', 'max:120'],
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



