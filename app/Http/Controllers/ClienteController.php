<?php

namespace App\Http\Controllers;

use App\Models\Cidade;
use App\Models\Cliente;
use App\Models\ClienteContrato;
use App\Models\ClienteGheFuncao;
use App\Models\Estado;
use App\Models\Funcao;
use App\Models\Funcionario;
use App\Models\ParametroCliente;
use App\Models\ParametroClienteAsoGrupo;
use App\Models\Servico;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\Tarefa;
use App\Models\UnidadeClinica;
use App\Models\User;
use App\Services\FuncionarioArquivosZipService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * LISTAGEM DE CLIENTES
     */
    public function index(Request $r)
    {
        $this->authorizeComercialAction('view');

        $q = trim((string) $r->query('q', ''));
        $texto = trim((string) $r->query('texto', ''));
        $documento = trim((string) $r->query('documento', ''));
        $status = $r->query('status', 'todos'); // todos|ativo|inativo
        $dataInicio = $r->query('data_inicio');
        $dataFim = $r->query('data_fim');
        $dataInicioNormalizada = null;
        $dataFimNormalizada = null;

        try {
            if (!empty($dataInicio)) {
                $dataInicioNormalizada = Carbon::parse($dataInicio)->toDateString();
            }
            if (!empty($dataFim)) {
                $dataFimNormalizada = Carbon::parse($dataFim)->toDateString();
            }
        } catch (\Throwable $e) {
            $dataInicioNormalizada = null;
            $dataFimNormalizada = null;
        }

        if (!empty($dataInicioNormalizada) && !empty($dataFimNormalizada) && $dataInicioNormalizada > $dataFimNormalizada) {
            [$dataInicioNormalizada, $dataFimNormalizada] = [$dataFimNormalizada, $dataInicioNormalizada];
        }

        if ($texto === '' && $documento === '' && $q !== '') {
            if (preg_match('/[A-Za-z]/', Str::ascii($q))) {
                $texto = $q;
            }

            if (preg_match('/\d/', $q)) {
                $documento = $q;
            }
        }

        $qText = preg_replace('/\s+/', ' ', trim($texto));
        $qText = is_string($qText) ? $qText : '';
        $qTextNormalized = $this->normalizeAutocompleteSearchValue($qText);
        $doc = preg_replace('/\D+/', '', $documento);

        $empresaId = $r->user()->empresa_id ?? 1;

        $clientesBaseQuery = Cliente::query()
            ->with(['userCliente', 'vendedor:id,name'])
            ->where('empresa_id', $empresaId)
            ->when($this->isComercialNaoMaster($r->user()), function ($q) use ($r) {
                $q->where('vendedor_id', (int) $r->user()->id);
            })
            ->when($status !== 'todos', fn($w) => $w->where('ativo', $status === 'ativo'))
            ->when(!empty($dataInicioNormalizada), fn($w) => $w->whereDate('clientes.created_at', '>=', $dataInicioNormalizada))
            ->when(!empty($dataFimNormalizada), fn($w) => $w->whereDate('clientes.created_at', '<=', $dataFimNormalizada));

        $clientes = (clone $clientesBaseQuery)
            ->when($qText !== '' || $doc !== '', function ($w) use ($qTextNormalized, $doc, $clientesBaseQuery) {
                $matchingIds = $this->findClienteIdsByNormalizedSearch($clientesBaseQuery, $qTextNormalized, $doc);

                if (empty($matchingIds)) {
                    $w->whereRaw('1 = 0');

                    return;
                }

                $w->whereIn('id', $matchingIds);
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $routePrefix = $this->routePrefix();

        $autocompleteOptions = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->when($this->isComercialNaoMaster($r->user()), function ($query) use ($r) {
                $query->where('vendedor_id', (int) $r->user()->id);
            })
            ->when($status !== 'todos', fn($query) => $query->where('ativo', $status === 'ativo'))
            ->when(!empty($dataInicioNormalizada), fn($query) => $query->whereDate('clientes.created_at', '>=', $dataInicioNormalizada))
            ->when(!empty($dataFimNormalizada), fn($query) => $query->whereDate('clientes.created_at', '<=', $dataFimNormalizada))
            ->orderBy('razao_social')
            ->get(['razao_social', 'nome_fantasia', 'email'])
            ->flatMap(function ($cliente) {
                $email = trim((string) $cliente->email);

                return array_filter([
                    $cliente->razao_social,
                    $cliente->nome_fantasia,
                    $email,
                ]);
            })
            ->unique()
            ->values();

        return view('clientes.index', compact(
            'clientes',
            'q',
            'texto',
            'documento',
            'status',
            'dataInicioNormalizada',
            'dataFimNormalizada',
            'routePrefix',
            'autocompleteOptions'
        ));
    }

    private function normalizeAutocompleteSearchValue(?string $value): string
    {
        $ascii = Str::ascii((string) $value);

        return Str::lower(preg_replace('/[^[:alnum:]]+/u', '', $ascii) ?? '');
    }

    private function normalizeDocumentoSearchValue(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function findClienteIdsByNormalizedSearch($baseQuery, string $qTextNormalized, string $doc): array
    {
        if ($qTextNormalized === '' && $doc === '') {
            return [];
        }

        return (clone $baseQuery)
            ->get(['id', 'razao_social', 'nome_fantasia', 'email', 'cnpj', 'cpf'])
            ->filter(fn (Cliente $cliente) => $this->clienteMatchesSearch($cliente, $qTextNormalized, $doc))
            ->pluck('id')
            ->all();
    }

    private function clienteMatchesSearch(Cliente $cliente, string $qTextNormalized, string $doc): bool
    {
        if ($qTextNormalized !== '') {
            foreach ([$cliente->razao_social, $cliente->nome_fantasia, $cliente->email] as $value) {
                $normalizedValue = $this->normalizeAutocompleteSearchValue($value);

                if ($normalizedValue !== '' && str_contains($normalizedValue, $qTextNormalized)) {
                    return true;
                }
            }
        }

        if ($doc !== '') {
            foreach ([$cliente->cnpj, $cliente->cpf] as $value) {
                $normalizedValue = $this->normalizeDocumentoSearchValue($value);

                if ($normalizedValue !== '' && str_contains($normalizedValue, $doc)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * CONSULTA CNPJ VIA API
     * (caso você queira usar via AJAX depois)
     */
    public function consultaCnpj($cnpj)
    {
        $cnpjLimpo = preg_replace('/\D/', '', (string) $cnpj);

        if (strlen($cnpjLimpo) !== 14) {
            return response()->json(['error' => 'CNPJ invalido.'], 422);
        }

        $dados = $this->consultaCnpjLocal($cnpjLimpo);
        if ($dados !== null) {
            return response()->json($dados);
        }

        $dados = $this->consultaCnpjReceitaWs($cnpjLimpo);
        if ($dados !== null) {
            return response()->json($dados);
        }

        $dados = $this->consultaCnpjBrasilApi($cnpjLimpo);
        if ($dados !== null) {
            return response()->json($dados);
        }

        return response()->json([
            'error' => 'Nao foi possivel consultar o CNPJ no momento. Tente novamente.',
        ], 502);
    }

    private function consultaCnpjLocal(string $cnpjLimpo): ?array
    {
        $cnpjMascarado = substr($cnpjLimpo, 0, 2) . '.'
            . substr($cnpjLimpo, 2, 3) . '.'
            . substr($cnpjLimpo, 5, 3) . '/'
            . substr($cnpjLimpo, 8, 4) . '-'
            . substr($cnpjLimpo, 12, 2);

        $cliente = Cliente::query()
            ->where(function ($q) use ($cnpjLimpo, $cnpjMascarado) {
                $q->where('cnpj', $cnpjLimpo)
                    ->orWhere('cnpj', $cnpjMascarado)
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(TRIM(cnpj), '.', ''), '-', ''), '/', ''), ' ', '') = ?", [$cnpjLimpo]);
            })
            ->first();

        if (!$cliente) {
            $sufixo = substr($cnpjLimpo, -8);
            $candidatos = Cliente::query()
                ->where('cnpj', 'like', "%{$sufixo}%")
                ->get();

            $cliente = $candidatos->first(function ($item) use ($cnpjLimpo) {
                $normalizado = preg_replace('/\D+/', '', (string) ($item->cnpj ?? ''));
                if ($normalizado === '') {
                    return false;
                }

                return $normalizado === $cnpjLimpo
                    || ltrim($normalizado, '0') === ltrim($cnpjLimpo, '0');
            });
        }

        if (!$cliente) {
            return null;
        }

        return [
            'razao_social'  => $cliente->razao_social,
            'nome_fantasia' => $cliente->nome_fantasia,
            'contato'       => $cliente->nome_fantasia ?: $cliente->razao_social,
            'telefone'      => $cliente->telefone,
            'cep'           => $cliente->cep,
            'endereco'      => $cliente->endereco,
            'bairro'        => $cliente->bairro,
            'complemento'   => $cliente->complemento,
            'uf'            => $cliente->uf,
            'municipio'     => null,
        ];
    }

    private function consultaCnpjReceitaWs(string $cnpjLimpo): ?array
    {
        try {
            $http = Http::timeout(10)->retry(1, 250);
            if (app()->environment('local')) {
                $http = $http->withOptions(['verify' => false]);
            }
            $resp = $http->get("https://www.receitaws.com.br/v1/cnpj/{$cnpjLimpo}");
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        if ($resp->failed()) {
            return null;
        }

        $dados = $resp->json();
        if (!is_array($dados)) {
            return null;
        }

        if (($dados['status'] ?? null) === 'ERROR') {
            return null;
        }

        return [
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
        ];
    }

    private function consultaCnpjBrasilApi(string $cnpjLimpo): ?array
    {
        try {
            $http = Http::timeout(10)->retry(1, 250);
            if (app()->environment('local')) {
                $http = $http->withOptions(['verify' => false]);
            }
            $resp = $http->get("https://brasilapi.com.br/api/cnpj/v1/{$cnpjLimpo}");
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        if ($resp->failed()) {
            return null;
        }

        $dados = $resp->json();
        if (!is_array($dados)) {
            return null;
        }

        $telefone = null;
        $ddd1 = trim((string) ($dados['ddd_telefone_1'] ?? ''));
        $ddd2 = trim((string) ($dados['ddd_telefone_2'] ?? ''));
        if ($ddd1 !== '') {
            $telefone = $ddd1;
        } elseif ($ddd2 !== '') {
            $telefone = $ddd2;
        }

        return [
            'razao_social'  => $dados['razao_social'] ?? null,
            'nome_fantasia' => $dados['nome_fantasia'] ?? null,
            'contato'       => $dados['nome_fantasia'] ?? null,
            'telefone'      => $telefone,
            'cep'           => $dados['cep'] ?? null,
            'endereco'      => $dados['logradouro'] ?? null,
            'bairro'        => $dados['bairro'] ?? null,
            'complemento'   => $dados['complemento'] ?? null,
            'uf'            => $dados['uf'] ?? null,
            'municipio'     => $dados['municipio'] ?? null,
        ];
    }
    public function cnpjExists(Request $request, string $cnpj)
    {
        $empresaId = $request->user()->empresa_id ?? 1;
        $documentoLimpo = preg_replace('/\D+/', '', (string) $cnpj);
        $tipoPessoa = strtoupper((string) $request->query('tipo_pessoa', ''));
        $ignorarId = $request->query('ignore') ? (int) $request->query('ignore') : null;

        if ($documentoLimpo === '') {
            return response()->json(['exists' => false]);
        }

        if ($tipoPessoa !== 'PF' && $tipoPessoa !== 'PJ') {
            $tipoPessoa = strlen($documentoLimpo) === 11 ? 'PF' : 'PJ';
        }

        $colunaDocumento = $tipoPessoa === 'PF' ? 'cpf' : 'cnpj';

        $query = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->whereRaw(
                "REPLACE(REPLACE(REPLACE({$colunaDocumento}, '.', ''), '-', ''), '/', '') = ?",
                [$documentoLimpo]
            );

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
        $this->authorizeComercialAction('create');

        $cliente = new Cliente();
        $empresaId = auth()->user()->empresa_id ?? 1;

        // se sua tabela tiver "uf", pode ajustar; aqui uso "sigla"
        $estados = Estado::orderBy('uf')->get(['uf', 'nome']);
        $formasPagamento = [
            'Pix',
            'Boleto',
            'Cartão de crédito',
            'Cartão de débito',
            'Transferência',
        ];
        $vendedores = User::query()
            ->where('empresa_id', $empresaId)
            ->whereHas('papel', function ($q) {
                $q->whereIn('nome', ['Master', 'Comercial']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $routePrefix = $this->routePrefix();

        return view('clientes.edit', [
            'cliente'         => $cliente,
            'estados'         => $estados,
            'cidadesDoEstado' => collect(),
            'ufSelecionada'   => null,
            'modo'            => 'create',
            'routePrefix'     => $routePrefix,
            'formasPagamento' => $formasPagamento,
            'vendedores'      => $vendedores,
            'parametro'       => null,
        ]);
    }

    /**
     * SALVAR NOVO
     */
    public function store(Request $r)
    {
        $this->authorizeComercialAction('create');

        $data = $this->validateData($r);
        $dadosComplementares = $this->validateDadosComplementares($r, new Cliente([
            'empresa_id' => $r->user()->empresa_id ?? 1,
        ]));

        $empresaId = $r->user()->empresa_id ?? 1;

        $data['empresa_id'] = $empresaId;
        $data['ativo']      = $r->boolean('ativo', true);
        $data['vendedor_id'] = $dadosComplementares['vendedor_id'];


        try {
            $cliente = DB::transaction(function () use ($data, $dadosComplementares, $r) {
                $cliente = Cliente::create($data);
                $this->syncPagamentoCliente($cliente, $dadosComplementares, $r);

                return $cliente;
            });
            $afterAction = $r->input('after_action');

            if ($afterAction === 'proposta') {
                return redirect()
                    ->route('comercial.propostas.create', ['cliente_id' => $cliente->id])
                    ->with('ok', 'Cliente cadastrado com sucesso!');
            }

            if ($afterAction === 'apresentacao') {
                $r->session()->put('apresentacao_proposta.cliente', [
                    'proposta_id' => null,
                    'cliente_id' => $cliente->id,
                    'cnpj' => $cliente->documento_principal,
                    'razao_social' => $cliente->razao_social,
                    'contato' => $cliente->nome_fantasia ?: $cliente->razao_social,
                    'telefone' => $cliente->telefone,
                ]);

                return redirect()
                    ->route('comercial.apresentacao.cliente')
                    ->with('ok', 'Cliente cadastrado com sucesso!');
            }

            return redirect()
                ->route($this->routeName('edit'), ['cliente' => $cliente->id, 'tab' => 'dados'])
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
        $this->authorizeComercialAction('view');
        $this->authorizeCliente($cliente);

        $routePrefix = $this->routePrefix();
        $contratoAtivo = null;

        if ($this->isComercialRoute()) {
            $contratoAtivo = ClienteContrato::query()
                ->where('empresa_id', auth()->user()->empresa_id)
                ->where('cliente_id', $cliente->id)
                ->where('status', 'ATIVO')
                ->withCount(['itens as itens_ativos_count' => function ($q) {
                    $q->where('ativo', true);
                }])
                ->latest('id')
                ->first();
        }

        return view('clientes.show', compact('cliente', 'routePrefix', 'contratoAtivo'));
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
                        if (!in_array(strlen($doc), [11, 14], true)) {
                            $fail('Informe um CPF ou CNPJ válido.');
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

            if ($email === null) {
                $clienteEmail = trim((string) ($cliente->email ?? ''));
                if ($clienteEmail !== '' && !\App\Models\User::where('email', $clienteEmail)->exists()) {
                    $email = $clienteEmail;
                }
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
    public function edit(Request $request, Cliente $cliente)
    {
        $this->authorizeComercialAction('update');
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
        $contratoAtivo = ClienteContrato::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->where('status', 'ATIVO')
            ->withCount(['itens as itens_ativos_count' => function ($q) {
                $q->where('ativo', true);
            }])
            ->latest('id')
            ->first();

        $servicos = $this->servicosDisponiveisParaCliente($empresaId);
        $servicoArtDisponivel = $this->servicoArtDisponivelParaCliente($empresaId);
        $funcoes = Funcao::where('empresa_id', $empresaId)->orderBy('nome')->get(['id', 'nome', 'ativo']);
        $clienteFuncoesIds = $cliente->funcoes()
            ->where('funcoes.empresa_id', $empresaId)
            ->pluck('funcoes.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $funcionariosPorFuncao = Funcionario::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('funcao_id')
            ->selectRaw('funcao_id, COUNT(*) as total')
            ->groupBy('funcao_id')
            ->pluck('total', 'funcao_id')
            ->mapWithKeys(fn ($total, $funcaoId) => [(int) $funcaoId => (int) $total])
            ->all();

        $ghesPorFuncao = ClienteGheFuncao::query()
            ->selectRaw('cliente_ghe_funcoes.funcao_id, COUNT(DISTINCT cliente_ghe_funcoes.cliente_ghe_id) as total')
            ->whereHas('ghe', function ($q) use ($empresaId, $cliente) {
                $q->where('empresa_id', $empresaId)
                    ->where('cliente_id', $cliente->id);
            })
            ->groupBy('cliente_ghe_funcoes.funcao_id')
            ->pluck('total', 'cliente_ghe_funcoes.funcao_id')
            ->mapWithKeys(fn ($total, $funcaoId) => [(int) $funcaoId => (int) $total])
            ->all();
        $clienteTemGheComFuncoes = !empty($ghesPorFuncao);

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
        $unidadesDisponiveis = UnidadeClinica::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get(['id', 'nome', 'ativo']);
        $unidadesPermitidasIds = $cliente->unidadesPermitidas()
            ->where('unidades_clinicas.empresa_id', $empresaId)
            ->pluck('unidades_clinicas.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $vendedores = User::query()
            ->where('empresa_id', $empresaId)
            ->whereHas('papel', function ($q) {
                $q->whereIn('nome', ['Master', 'Comercial']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $arquivosQuery = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->where(function ($q) {
                $q->whereNotNull('path_documento_cliente')
                    ->orWhereHas('anexos', function ($aq) {
                        $aq->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['certificado_treinamento']);
                    });
            })
            ->with(['servico', 'coluna', 'anexos']);

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $arquivosQuery->where('titulo', 'like', '%' . $q . '%');
        }

        if ($request->filled('data_inicio')) {
            $arquivosQuery->whereDate('finalizado_em', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $arquivosQuery->whereDate('finalizado_em', '<=', $request->input('data_fim'));
        }

        if ($request->filled('servico')) {
            $arquivosQuery->where('servico_id', (int) $request->input('servico'));
        }

        $arquivos = $arquivosQuery
            ->orderByDesc('finalizado_em')
            ->orderByDesc('updated_at')
            ->get();

        $funcionariosComArquivosIds = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereNotNull('funcionario_id')
            ->where(function ($q) {
                $q->whereNotNull('path_documento_cliente')
                    ->orWhereHas('anexos');
            })
            ->distinct()
            ->pluck('funcionario_id')
            ->filter()
            ->values();

        $funcionariosComArquivos = $funcionariosComArquivosIds->isNotEmpty()
            ? Funcionario::query()
                ->whereIn('id', $funcionariosComArquivosIds->all())
                ->orderBy('nome')
                ->get(['id', 'nome'])
            : collect();

        $servicosArquivosIds = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->where(function ($q) {
                $q->whereNotNull('path_documento_cliente')
                    ->orWhereHas('anexos', function ($aq) {
                        $aq->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['certificado_treinamento']);
                    });
            })
            ->distinct()
            ->pluck('servico_id')
            ->filter()
            ->values();

        $servicosArquivos = $servicosArquivosIds->isNotEmpty()
            ? Servico::query()
                ->whereIn('id', $servicosArquivosIds->all())
                ->orderBy('nome')
                ->get()
            : collect();

        $userExistente = \App\Models\User::query()
            ->where('cliente_id', $cliente->id)
            ->first();
        $senhaSugerida = \Illuminate\Support\Str::password(10);

        return view('clientes.edit', [
            'cliente'         => $cliente,
            'estados'         => $estados,
            'cidadesDoEstado' => $cidadesDoEstado,
            'ufSelecionada'   => $ufSelecionada,
            'modo'            => 'edit',
            'routePrefix'     => $routePrefix,
            'servicos'        => $servicos,
            'servicoArtDisponivel' => $servicoArtDisponivel,
            'funcoes'         => $funcoes,
            'clienteFuncoesIds' => $clienteFuncoesIds,
            'funcionariosPorFuncao' => $funcionariosPorFuncao,
            'ghesPorFuncao' => $ghesPorFuncao,
            'clienteTemGheComFuncoes' => $clienteTemGheComFuncoes,
            'treinamentos'    => $treinamentos,
            'formasPagamento' => $formasPagamento,
            'parametro'       => $parametro,
            'parametroAsoGrupos' => $parametroAsoGrupos,
            'unidadesDisponiveis' => $unidadesDisponiveis,
            'unidadesPermitidasIds' => $unidadesPermitidasIds,
            'vendedores'      => $vendedores,
            'arquivos'        => $arquivos,
            'servicosArquivos' => $servicosArquivos,
            'funcionariosComArquivos' => $funcionariosComArquivos,
            'userExistente'    => $userExistente,
            'senhaSugerida'    => $senhaSugerida,
            'contratoAtivo'    => $contratoAtivo,
        ]);
    }

    private function servicosDisponiveisParaCliente(int $empresaId)
    {
        $esocialId = config('services.esocial_id');

        $servicos = Servico::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->when($esocialId, fn ($q) => $q->where('id', '!=', $esocialId))
            ->orderBy('nome')
            ->get();

        $temArt = $servicos->contains(function ($servico) {
            return mb_strtolower((string) $servico->nome, 'UTF-8') === 'art';
        });

        if (!$temArt) {
            $servicoArt = Servico::query()
                ->where('ativo', true)
                ->whereRaw('LOWER(nome) = ?', ['art'])
                ->when($esocialId, fn ($q) => $q->where('id', '!=', $esocialId))
                ->orderByRaw('CASE WHEN empresa_id = ? THEN 0 WHEN empresa_id = 1 THEN 1 ELSE 2 END', [$empresaId])
                ->orderBy('id')
                ->first();

            if ($servicoArt) {
                $servicos->push($servicoArt);
                $servicos = $servicos->sortBy('nome', SORT_NATURAL | SORT_FLAG_CASE)->values();
            }
        }

        return $servicos;
    }

    private function servicoArtDisponivelParaCliente(int $empresaId): ?Servico
    {
        return Servico::query()
            ->whereRaw('LOWER(nome) = ?', ['art'])
            ->where(function ($query) use ($empresaId) {
                $query->where('empresa_id', $empresaId)
                    ->orWhere('empresa_id', 1);
            })
            ->orderByRaw('CASE WHEN empresa_id = ? THEN 0 WHEN empresa_id = 1 THEN 1 ELSE 2 END', [$empresaId])
            ->orderByDesc('ativo')
            ->orderBy('id')
            ->first();
    }

    public function downloadArquivosFuncionario(
        Request $request,
        Cliente $cliente,
        Funcionario $funcionario,
        FuncionarioArquivosZipService $zipService
    ) {
        $this->authorizeCliente($cliente);
        abort_unless((int) $funcionario->cliente_id === (int) $cliente->id, 403);

        try {
            $zipPath = $zipService->gerarZip($cliente, $funcionario);
        } catch (\RuntimeException $e) {
            return back()->with('erro', $e->getMessage());
        }
        $zipName = 'arquivos-' . str_replace(' ', '-', mb_strtolower($funcionario->nome)) . '.zip';

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    public function downloadArquivosSelecionados(
        Request $request,
        Cliente $cliente,
        FuncionarioArquivosZipService $zipService
    ) {
        $this->authorizeCliente($cliente);
        $tarefaIds = collect((array) $request->input('tarefa_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (count($tarefaIds) === 1) {
            try {
                $arquivos = $zipService->listarArquivosPorIds($cliente, $tarefaIds);
            } catch (\RuntimeException $e) {
                return back()->with('erro', $e->getMessage());
            }

            if (count($arquivos) === 1) {
                $arquivo = $arquivos[0];

                return Storage::disk($arquivo['disk'])->download($arquivo['path'], $arquivo['name']);
            }
        }

        try {
            $zipPath = $zipService->gerarZipPorIds($cliente, $tarefaIds);
        } catch (\RuntimeException $e) {
            return back()->with('erro', $e->getMessage());
        }

        return response()->download($zipPath, 'arquivos-selecionados.zip')->deleteFileAfterSend(true);
    }

    /**
     * ATUALIZAR
     */
    public function update(Request $r, Cliente $cliente)
    {
        $this->authorizeComercialAction('update');
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
                ->route($this->routeName('edit'), ['cliente' => $cliente->id, 'tab' => 'dados'])
                ->with('ok', 'Vendedor atualizado com sucesso.');
        }

        $data = $this->validateData($r);
        $dadosComplementares = $this->shouldValidateDadosComplementares($r, $cliente)
            ? $this->validateDadosComplementares($r, $cliente)
            : null;

        $data['empresa_id'] = $cliente->empresa_id ?? ($r->user()->empresa_id ?? 1);
        $data['ativo']      = $r->boolean('ativo');

        try {
            DB::transaction(function () use ($cliente, $data, $dadosComplementares, $r) {
                $cliente->update($data);

                if (!$dadosComplementares) {
                    return;
                }

                $cliente->update([
                    'vendedor_id' => $dadosComplementares['vendedor_id'],
                ]);

                $this->syncPagamentoCliente($cliente, $dadosComplementares, $r);
            });

            return redirect()
                ->route($this->routeName('edit'), ['cliente' => $cliente->id, 'tab' => 'dados'])
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
        $this->authorizeComercialAction('delete');
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
        $tipoPessoa = strtoupper((string) $r->input('tipo_pessoa', 'PJ'));

        $data = $r->validate(
            [
                'tipo_pessoa'    => ['required', Rule::in(['PF', 'PJ'])],
                'razao_social'   => ['required', 'string', 'max:255'],
                'nome_fantasia'  => ['nullable', 'string', 'max:255'],
                'cpf'            => [
                    Rule::requiredIf(fn () => $tipoPessoa === 'PF'),
                    'nullable',
                    'string',
                    'max:14',
                    Rule::unique('clientes', 'cpf')
                        ->where(fn ($q) => $q->where('empresa_id', $empresaId))
                        ->ignore($clienteId),
                ],
                'cnpj'           => [
                    Rule::requiredIf(fn () => $tipoPessoa === 'PJ'),
                    'nullable',
                    'string',
                    'max:20',
                    Rule::unique('clientes', 'cnpj')
                        ->where(fn ($q) => $q->where('empresa_id', $empresaId))
                        ->ignore($clienteId),
                ],
                'email'          => ['nullable', 'email', 'max:255'],
                'telefone'       => ['nullable', 'string', 'max:30'],
                'contato'        => ['nullable', 'string', 'max:120'],
                'telefone_2'     => ['nullable', 'string', 'max:30'],
                'tipo_cliente'   => ['required', Rule::in(['parceiro', 'final'])],
                'observacao'     => [
                    'nullable',
                    'string',
                    'max:4000',
                    Rule::requiredIf(fn () => $r->input('tipo_cliente') === 'parceiro'),
                ],
                'cep'            => ['nullable', 'string', 'max:20'],
                'endereco'       => ['nullable', 'string', 'max:255'],
                'numero'         => ['nullable', 'string', 'max:20'],
                'bairro'         => ['nullable', 'string', 'max:150'],
                'complemento'    => ['nullable', 'string', 'max:255'],
                'cidade_id'      => ['required', 'exists:cidades,id'],
            ],
            [
                'tipo_pessoa.required'  => 'Selecione se o cliente e PF ou PJ.',
                'tipo_pessoa.in'        => 'Tipo de pessoa invalido.',
                'razao_social.required' => 'Informe o nome do cliente.',
                'razao_social.max'      => 'O nome deve ter no máximo 255 caracteres.',
                'cpf.required'          => 'Informe o CPF do cliente.',
                'cpf.max'               => 'O CPF está muito longo. Confira o número digitado.',
                'cnpj.required'         => 'Informe o CNPJ do cliente.',
                'cnpj.max'              => 'O CNPJ está muito longo. Confira o número digitado.',
                'email.email'           => 'Informe um e-mail válido (ex: nome@empresa.com).',
                'tipo_cliente.required' => 'Selecione se o cliente Parceiro ou Final.',
                'tipo_cliente.in'       => 'Tipo de cliente inválido.',
                'observacao.required'   => 'Informe a observacao com os detalhes negociados para cliente parceiro.',
                'observacao.max'        => 'A observação deve ter no máximo 4000 caracteres.',
                'cidade_id.required'    => 'Selecione a cidade do cliente.',
                'cidade_id.exists'      => 'Selecione uma cidade válida.',
            ]
        );

        $data['tipo_pessoa'] = $tipoPessoa;
        $data['cpf'] = $this->normalizeDocumento($data['cpf'] ?? null);
        $data['cnpj'] = $this->normalizeDocumento($data['cnpj'] ?? null);

        if ($tipoPessoa === 'PF') {
            $data['cnpj'] = null;
            $data['nome_fantasia'] = null;
        } else {
            $data['cpf'] = null;
        }

        return $this->normalizeClienteUppercase($data);
    }

    protected function validateDadosComplementares(Request $r, Cliente $cliente): array
    {
        $empresaId = $cliente->empresa_id ?? ($r->user()->empresa_id ?? 1);

        return $r->validate([
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
            'forma_pagamento' => ['required', 'string', 'max:80'],
            'email_envio_fatura' => ['nullable', 'email', 'max:255'],
            'vencimento_servicos' => ['required', 'integer', 'min:1', 'max:31'],
        ], [
            'forma_pagamento.required' => 'Selecione a forma de pagamento.',
            'email_envio_fatura.email' => 'Informe um e-mail válido para envio da fatura.',
            'vencimento_servicos.required' => 'Informe o vencimento dos serviços.',
            'vencimento_servicos.integer' => 'O vencimento dos serviços deve ser um número inteiro.',
            'vencimento_servicos.min' => 'O vencimento dos serviços deve ser no mínimo 1.',
            'vencimento_servicos.max' => 'O vencimento dos serviços deve ser no máximo 31.',
            'vendedor_id.required' => 'Selecione o vendedor responsável.',
        ]);
    }

    protected function shouldValidateDadosComplementares(Request $r, Cliente $cliente): bool
    {
        if (!$cliente->exists) {
            return false;
        }

        return $r->hasAny([
            'vendedor_id',
            'forma_pagamento',
            'email_envio_fatura',
            'vencimento_servicos',
        ]);
    }

    protected function syncPagamentoCliente(Cliente $cliente, array $data, Request $r): void
    {
        $empresaId = $cliente->empresa_id ?? ($r->user()->empresa_id ?? 1);

        $parametro = ParametroCliente::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->latest('id')
            ->first();

        $payload = [
            'empresa_id' => $empresaId,
            'cliente_id' => $cliente->id,
            'vendedor_id' => $data['vendedor_id'],
            'forma_pagamento' => $data['forma_pagamento'],
            'email_envio_fatura' => !empty($data['email_envio_fatura']) ? mb_strtolower(trim((string) $data['email_envio_fatura'])) : null,
            'vencimento_servicos' => (int) $data['vencimento_servicos'],
            'incluir_esocial' => $parametro?->incluir_esocial ?? false,
            'esocial_qtd_funcionarios' => $parametro?->esocial_qtd_funcionarios,
            'esocial_valor_mensal' => $parametro?->esocial_valor_mensal ?? 0,
            'valor_total' => $parametro?->valor_total ?? 0,
            'prazo_dias' => $parametro?->prazo_dias,
            'observacoes' => $parametro?->observacoes,
        ];

        if ($parametro) {
            $parametro->update($payload);

            return;
        }

        ParametroCliente::create($payload);
    }

    private function normalizeDocumento(?string $value): ?string
    {
        $value = preg_replace('/\D+/', '', (string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizeClienteUppercase(array $data): array
    {
        $fieldsToUpper = [
            'razao_social',
            'nome_fantasia',
            'contato',
            'observacao',
            'endereco',
            'bairro',
            'complemento',
        ];

        foreach ($fieldsToUpper as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null) {
                continue;
            }

            $value = trim((string) $data[$field]);
            $data[$field] = function_exists('mb_strtoupper')
                ? mb_strtoupper($value, 'UTF-8')
                : strtoupper($value);
        }

        return $data;
    }


    protected function authorizeCliente(Cliente $cliente): void
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id ?? 1;

        if ($cliente->empresa_id != $empresaId) {
            abort(403);
        }

        if ($this->isComercialNaoMaster($user) && (int) $cliente->vendedor_id !== (int) $user->id) {
            abort(403);
        }
    }

    private function isComercialRoute(): bool
    {
        return request()->routeIs('comercial.clientes.*');
    }

    private function isComercialNaoMaster(?User $user): bool
    {
        return $this->isComercialRoute() && $user && !$user->hasPapel('Master') && $user->hasPapel('Comercial');
    }

    private function authorizeComercialAction(string $action): void
    {
        if (!$this->isComercialRoute()) {
            return;
        }

        $user = auth()->user();
        if (!$user) {
            abort(403);
        }

        if ($user->hasPapel('Master')) {
            return;
        }

        if (!$user->hasPapel('Comercial')) {
            abort(403);
        }

        $chave = 'comercial.clientes.' . $action;
        $temPermissao = $user->papel()
            ->whereHas('permissoes', fn ($q) => $q->where('chave', $chave))
            ->exists();

        if (!$temPermissao) {
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

        // 2) Cidades do banco (fallback garantido)
        $cidadesDb = Cidade::where('estado_id', $estado->id)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        if ($cidadesDb->isEmpty()) {
            return response()->json([]);
        }

        // chave normalizada -> cidade_id
        $map = [];
        foreach ($cidadesDb as $c) {
            $nomeNormalizado = strtolower(
                preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $c->nome))
            );
            $map[$nomeNormalizado] = $c->id;
        }

        // 3) API IBGE
        try {
            $resp = Http::timeout(8)
                ->retry(1, 150)
                ->get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios");
        } catch (\Throwable $e) {
            $resp = null;
        }

        if (! $resp || ! $resp->successful()) {
            return response()->json(
                $cidadesDb->map(fn ($cidade) => [
                    'id' => $cidade->id,
                    'nome' => $cidade->nome,
                ])->values()
            );
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

        if (empty($resultado)) {
            return response()->json(
                $cidadesDb->map(fn ($cidade) => [
                    'id' => $cidade->id,
                    'nome' => $cidade->nome,
                ])->values()
            );
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
