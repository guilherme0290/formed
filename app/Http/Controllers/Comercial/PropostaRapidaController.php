<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\ExamesTabPreco;
use App\Models\Proposta;
use App\Models\PropostaItens;
use App\Models\Servico;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\TreinamentoNrsTabPreco;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PropostaRapidaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $isMaster = $user->hasPapel('Master');

        $q = trim((string) $request->query('q', ''));

        $query = Proposta::query()
            ->rapida()
            ->with(['cliente', 'empresa', 'vendedor'])
            ->where('empresa_id', $empresaId);

        if (!$isMaster) {
            $query->where('vendedor_id', $user->id);
        }

        if ($q !== '') {
            $query->whereHas('cliente', fn ($clienteQuery) => $clienteQuery->where('razao_social', 'like', '%' . $q . '%'));
        }

        $propostas = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('comercial.propostas-rapidas.index', [
            'propostas' => $propostas,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;
        $empresa = Empresa::findOrFail($empresaId);
        $propostaBase = null;

        $duplicarDe = (int) $request->query('duplicar_de');
        if ($duplicarDe > 0) {
            $propostaBase = Proposta::query()
                ->with(['cliente', 'empresa', 'vendedor', 'itens'])
                ->findOrFail($duplicarDe);

            $this->authorizeProposta($request, $propostaBase);
        }

        $clienteSelecionado = null;
        if (!$propostaBase) {
            $clienteSelecionadoId = (int) $request->query('cliente_id');
            if ($clienteSelecionadoId > 0) {
                $clienteSelecionado = Cliente::query()
                    ->where('empresa_id', $empresaId)
                    ->find($clienteSelecionadoId);
            }
        }

        return view('comercial.propostas-rapidas.form', [
            'proposta' => null,
            'propostaBase' => $propostaBase,
            'empresa' => $empresa,
            'clientes' => $this->clientesParaSelecao($empresaId),
            'catalogo' => $this->catalogo($empresaId),
            'clienteSelecionado' => $clienteSelecionado,
            'descontoMaximo' => $this->descontoMaximo($user),
            'clienteModoDuplicacao' => $request->query('cliente_modo'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request);
    }

    public function edit(Request $request, Proposta $proposta): View
    {
        $this->authorizeProposta($request, $proposta);

        return view('comercial.propostas-rapidas.form', [
            'proposta' => $proposta->load(['cliente', 'empresa', 'itens']),
            'empresa' => $proposta->empresa,
            'clientes' => $this->clientesParaSelecao($proposta->empresa_id),
            'catalogo' => $this->catalogo($proposta->empresa_id),
            'clienteSelecionado' => $proposta->cliente,
            'descontoMaximo' => $this->descontoMaximo($request->user()),
        ]);
    }

    public function update(Request $request, Proposta $proposta): RedirectResponse
    {
        $this->authorizeProposta($request, $proposta);

        return $this->save($request, $proposta);
    }

    public function show(Request $request, Proposta $proposta): View
    {
        $this->authorizeProposta($request, $proposta);

        return view('comercial.propostas-rapidas.show', [
            'proposta' => $proposta->load(['cliente', 'empresa', 'vendedor', 'itens']),
        ]);
    }

    public function destroy(Request $request, Proposta $proposta): RedirectResponse
    {
        $this->authorizeProposta($request, $proposta);

        $proposta->itens()->delete();
        $proposta->delete();

        return redirect()
            ->route('comercial.propostas.rapidas.index')
            ->with('ok', 'Proposta rápida removida.');
    }

    public function pdf(Request $request, Proposta $proposta)
    {
        $this->authorizeProposta($request, $proposta);

        $proposta->load(['cliente', 'empresa', 'vendedor', 'itens']);

        $pdf = Pdf::loadView('comercial.propostas-rapidas.pdf', [
            'proposta' => $proposta,
        ])->setPaper('a4');

        $filename = 'proposta-rapida-' . ($proposta->codigo ?? $proposta->id) . '.pdf';

        return $pdf->download($filename);
    }

    private function save(Request $request, ?Proposta $proposta = null): RedirectResponse
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        $data = $request->validate([
            'cliente_modo' => ['required', 'in:existente,novo'],
            'cliente_existente_id' => ['nullable', 'integer'],
            'novo_cnpj' => ['nullable', 'string', 'max:30'],
            'novo_razao_social' => ['nullable', 'string', 'max:255'],
            'novo_endereco' => ['nullable', 'string', 'max:255'],
            'novo_telefone' => ['nullable', 'string', 'max:30'],
            'novo_email' => ['nullable', 'email', 'max:255'],
            'prazo_dias' => ['required', 'integer', 'min:1', 'max:365'],
            'mostrar_resumo_financeiro' => ['nullable', 'boolean'],
            'observacoes' => ['nullable', 'string', 'max:4000'],
            'desconto_percentual' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items_payload' => ['required', 'string'],
        ]);

        $itens = json_decode($data['items_payload'], true);
        if (!is_array($itens)) {
            return back()->withErrors(['items_payload' => 'Itens da proposta inválidos.'])->withInput();
        }

        Validator::make(
            ['itens' => $itens],
            [
                'itens' => ['required', 'array', 'min:1'],
                'itens.*.categoria' => ['required', 'in:SERVICO,EXAME,TREINAMENTO'],
                'itens.*.nome' => ['required', 'string', 'max:255'],
                'itens.*.descricao' => ['nullable', 'string', 'max:255'],
                'itens.*.origem_id' => ['nullable', 'integer'],
                'itens.*.valor_unitario' => ['required', 'numeric', 'min:0'],
                'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            ]
        )->validate();

        $descontoPercentual = round((float) ($data['desconto_percentual'] ?? 0), 2);
        $descontoMaximo = $this->descontoMaximo($user);
        if (!$user->hasPapel('Master') && $descontoPercentual > $descontoMaximo) {
            return back()
                ->withErrors(['desconto_percentual' => 'O desconto informado ultrapassa o limite liberado para este vendedor.'])
                ->withInput();
        }

        $cliente = $this->resolveCliente($request, $data, $proposta);

        $subtotal = collect($itens)->sum(function (array $item) {
            return round((float) $item['valor_unitario'] * (int) $item['quantidade'], 2);
        });
        $descontoValor = round($subtotal * ($descontoPercentual / 100), 2);
        $totalFinal = max(0, round($subtotal - $descontoValor, 2));
        $mostrarResumoFinanceiro = $request->boolean('mostrar_resumo_financeiro', true);

        $proposta = DB::transaction(function () use (
            $proposta,
            $empresaId,
            $cliente,
            $user,
            $subtotal,
            $descontoPercentual,
            $descontoValor,
            $totalFinal,
            $mostrarResumoFinanceiro,
            $data,
            $itens
        ) {
            $codigo = $proposta?->codigo ?: $this->nextRapidCode($empresaId);

            $payload = [
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'vendedor_id' => $proposta?->vendedor_id ?? $user->id,
                'codigo' => $codigo,
                'tipo_modelo' => 'RAPIDA',
                'forma_pagamento' => 'A combinar',
                'incluir_esocial' => false,
                'esocial_qtd_funcionarios' => null,
                'esocial_valor_mensal' => 0,
                'valor_bruto' => $subtotal,
                'desconto_percentual' => $descontoPercentual,
                'desconto_valor' => $descontoValor,
                'valor_total' => $totalFinal,
                'mostrar_resumo_financeiro' => $mostrarResumoFinanceiro,
                'status' => 'PENDENTE',
                'prazo_dias' => (int) $data['prazo_dias'],
                'vencimento_servicos' => null,
                'observacoes' => $data['observacoes'] ?? null,
                'pipeline_status' => 'CONTATO_INICIAL',
                'pipeline_updated_at' => now(),
                'pipeline_updated_by' => $user->id,
                'perdido_motivo' => null,
                'perdido_observacao' => null,
                'public_token' => null,
                'public_responded_at' => null,
            ];

            if ($proposta) {
                $proposta->update($payload);
                $proposta->itens()->delete();
            } else {
                $proposta = Proposta::create($payload);
            }

            $servicoTreinamentoId = $this->treinamentoServicoId($empresaId);
            $servicoExameId = (int) (config('services.exame_id') ?? 0);

            foreach ($itens as $item) {
                $categoria = strtoupper((string) ($item['categoria'] ?? 'SERVICO'));
                $servicoId = null;
                $meta = [
                    'origem_id' => $item['origem_id'] ?? null,
                    'categoria' => $categoria,
                ];

                if ($categoria === 'SERVICO') {
                    $servicoId = !empty($item['origem_id']) ? (int) $item['origem_id'] : null;
                } elseif ($categoria === 'EXAME') {
                    $servicoId = $servicoExameId > 0 ? $servicoExameId : null;
                } elseif ($categoria === 'TREINAMENTO') {
                    $servicoId = $servicoTreinamentoId;
                }

                $valorUnitario = round((float) $item['valor_unitario'], 2);
                $quantidade = (int) $item['quantidade'];
                $valorTotal = round($valorUnitario * $quantidade, 2);

                PropostaItens::create([
                    'proposta_id' => $proposta->id,
                    'servico_id' => $servicoId,
                    'tipo' => $categoria,
                    'nome' => $item['nome'],
                    'descricao' => $item['descricao'] ?? null,
                    'valor_unitario' => $valorUnitario,
                    'acrescimo' => 0,
                    'desconto' => 0,
                    'quantidade' => $quantidade,
                    'prazo' => null,
                    'valor_total' => $valorTotal,
                    'meta' => $meta,
                ]);
            }

            return $proposta;
        });

        return redirect()
            ->route('comercial.propostas.rapidas.show', $proposta)
            ->with('ok', $request->routeIs('*.update') ? 'Proposta rápida atualizada.' : 'Proposta rápida criada.');
    }

    private function authorizeProposta(Request $request, Proposta $proposta): void
    {
        abort_unless($proposta->isRapida(), 404);

        $user = $request->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        if (!$user->hasPapel('Master')) {
            abort_unless((int) $proposta->vendedor_id === (int) $user->id, 403);
        }
    }

    private function resolveCliente(Request $request, array $data, ?Proposta $proposta = null): Cliente
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        if (($data['cliente_modo'] ?? '') === 'existente') {
            $clienteId = (int) ($data['cliente_existente_id'] ?? 0);
            if ($clienteId <= 0 && $proposta) {
                $clienteId = (int) $proposta->cliente_id;
            }

            $cliente = Cliente::query()
                ->where('empresa_id', $empresaId)
                ->find($clienteId);

            abort_if(!$cliente, 403, 'Cliente da proposta não encontrado para esta empresa.');

            return $cliente;
        }

        $cnpj = $this->normalizeDocumento($data['novo_cnpj'] ?? null);
        $razaoSocial = trim((string) ($data['novo_razao_social'] ?? ''));
        $endereco = trim((string) ($data['novo_endereco'] ?? ''));
        $telefone = trim((string) ($data['novo_telefone'] ?? ''));
        $email = trim((string) ($data['novo_email'] ?? ''));

        if (strlen($cnpj) !== 14) {
            throw ValidationException::withMessages([
                'novo_cnpj' => 'Informe um CNPJ válido para o novo cliente.',
            ]);
        }

        if ($razaoSocial === '' || $endereco === '') {
            throw ValidationException::withMessages([
                'novo_razao_social' => 'Informe a razão social do novo cliente.',
                'novo_endereco' => 'Informe o endereço do novo cliente.',
            ]);
        }

        $cliente = Cliente::query()
            ->where('empresa_id', $empresaId)
            ->whereRaw("REPLACE(REPLACE(REPLACE(cnpj, '.', ''), '-', ''), '/', '') = ?", [$cnpj])
            ->first();

        if ($cliente) {
            return $cliente;
        }

        return Cliente::create([
            'empresa_id' => $empresaId,
            'vendedor_id' => $user->id,
            'tipo_pessoa' => 'PJ',
            'razao_social' => $this->upper($razaoSocial),
            'nome_fantasia' => null,
            'cnpj' => $cnpj,
            'email' => $email !== '' ? $email : null,
            'telefone' => $telefone !== '' ? $telefone : null,
            'tipo_cliente' => 'final',
            'endereco' => $this->upper($endereco),
            'ativo' => true,
        ]);
    }

    private function clientesParaSelecao(int $empresaId): Collection
    {
        return Cliente::query()
            ->where('empresa_id', $empresaId)
            ->orderBy('razao_social')
            ->get()
            ->map(function (Cliente $cliente) {
                return [
                    'id' => $cliente->id,
                    'razao_social' => $cliente->razao_social,
                    'cnpj' => $cliente->cnpj,
                    'documento_principal' => $cliente->documento_principal,
                    'endereco' => $cliente->endereco,
                    'email' => $cliente->email,
                    'telefone' => $cliente->telefone,
                ];
            })
            ->values();
    }

    private function catalogo(int $empresaId): array
    {
        $esocialId = (int) (config('services.esocial_id') ?? 0);
        $exameServicoId = (int) (config('services.exame_id') ?? 0);
        $treinamentoServicoId = (int) ($this->treinamentoServicoId($empresaId) ?? 0);
        $padrao = TabelaPrecoPadrao::query()
            ->where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->first();

        $servicos = Servico::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->when($esocialId > 0, fn ($query) => $query->where('id', '!=', $esocialId))
            ->when($exameServicoId > 0, fn ($query) => $query->where('id', '!=', $exameServicoId))
            ->when($treinamentoServicoId > 0, fn ($query) => $query->where('id', '!=', $treinamentoServicoId))
            ->where(function ($query) {
                $query->whereRaw('LOWER(nome) not like ?', ['%exame%'])
                    ->orWhereRaw('LOWER(nome) like ?', ['%toxico%'])
                    ->orWhereRaw('LOWER(nome) like ?', ['%toxicol%']);
            })
            ->whereRaw('LOWER(nome) not like ?', ['%aso%'])
            ->orderBy('nome')
            ->get()
            ->map(function (Servico $servico) use ($padrao) {
                $tabelaItem = $this->precoPadraoServico($padrao, $servico);

                return [
                    'origem_id' => $servico->id,
                    'categoria' => 'SERVICO',
                    'nome' => $servico->nome,
                    'descricao' => $tabelaItem?->descricao ?: $servico->descricao,
                    'valor_unitario' => (float) ($tabelaItem?->preco ?? $servico->valor_base ?? 0),
                ];
            })
            ->values()
            ->all();

        $exames = ExamesTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('titulo')
            ->get()
            ->map(fn (ExamesTabPreco $exame) => [
                'origem_id' => $exame->id,
                'categoria' => 'EXAME',
                'nome' => $exame->titulo,
                'descricao' => $exame->descricao,
                'valor_unitario' => (float) ($exame->preco ?? 0),
            ])
            ->values()
            ->all();

        $precosTreinamento = $this->treinamentoPrecosPorCodigo($empresaId);

        $treinamentos = TreinamentoNrsTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->orderBy('titulo')
            ->get()
            ->map(function (TreinamentoNrsTabPreco $treinamento) use ($precosTreinamento) {
                $preco = 0.0;
                if (!empty($treinamento->codigo) && isset($precosTreinamento[$treinamento->codigo])) {
                    $preco = (float) ($precosTreinamento[$treinamento->codigo]->preco ?? 0);
                }

                return [
                    'origem_id' => $treinamento->id,
                    'categoria' => 'TREINAMENTO',
                    'nome' => trim(($treinamento->codigo ? $treinamento->codigo . ' - ' : '') . $treinamento->titulo),
                    'descricao' => $treinamento->titulo,
                    'valor_unitario' => $preco,
                ];
            })
            ->values()
            ->all();

        return [
            'servicos' => $servicos,
            'exames' => $exames,
            'treinamentos' => $treinamentos,
        ];
    }

    private function treinamentoServicoId(int $empresaId): ?int
    {
        $configurado = (int) (config('services.treinamento_id') ?? 0);
        if ($configurado > 0) {
            return $configurado;
        }

        return Servico::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->where(function ($query) {
                $query->whereRaw('LOWER(tipo) = ?', ['treinamento'])
                    ->orWhereRaw('LOWER(nome) like ?', ['%treinamento%']);
            })
            ->orderBy('id')
            ->value('id');
    }

    private function treinamentoPrecosPorCodigo(int $empresaId): array
    {
        $servicoId = $this->treinamentoServicoId($empresaId);
        if (!$servicoId) {
            return [];
        }

        $padrao = TabelaPrecoPadrao::query()
            ->where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->first();

        if (!$padrao) {
            return [];
        }

        return $padrao->itens()
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->whereNotNull('codigo')
            ->get()
            ->keyBy('codigo')
            ->all();
    }

    private function precoPadraoServico(?TabelaPrecoPadrao $padrao, Servico $servico): ?TabelaPrecoItem
    {
        if (!$padrao) {
            return null;
        }

        return TabelaPrecoItem::query()
            ->where('tabela_preco_padrao_id', $padrao->id)
            ->where('servico_id', $servico->id)
            ->where('ativo', true)
            ->orderByRaw(
                "CASE
                    WHEN LOWER(COALESCE(descricao, '')) = ? THEN 0
                    WHEN LOWER(COALESCE(descricao, '')) LIKE ? THEN 1
                    WHEN COALESCE(descricao, '') = '' THEN 2
                    ELSE 3
                END",
                [mb_strtolower(trim((string) $servico->nome), 'UTF-8'), '%' . mb_strtolower(trim((string) $servico->nome), 'UTF-8') . '%']
            )
            ->orderByRaw('CHAR_LENGTH(COALESCE(descricao, \'\')) DESC')
            ->orderBy('descricao')
            ->first();
    }

    private function descontoMaximo($user): float
    {
        if ($user->hasPapel('Master')) {
            return 100.0;
        }

        return round((float) ($user->proposta_desconto_max_percentual ?? 0), 2);
    }

    private function nextRapidCode(int $empresaId): string
    {
        $prefix = 'PR-';
        $propostas = Proposta::query()
            ->rapida()
            ->where('empresa_id', $empresaId)
            ->lockForUpdate()
            ->get(['id', 'codigo']);

        $parsedSequence = $propostas
            ->pluck('codigo')
            ->map(function ($code) {
                if (!is_string($code)) {
                    return 0;
                }

                $patterns = [
                    '/^PR-(\d{2,})$/',
                    '/^PRR-(\d{2,})$/',
                    '/^PRR-\d{8}-(\d{2,})$/',
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $code, $matches)) {
                        return (int) $matches[1];
                    }
                }

                return 0;
            })
            ->max() ?? 0;

        $nextSequence = max($propostas->count(), $parsedSequence) + 1;

        return $prefix . str_pad((string) $nextSequence, 2, '0', STR_PAD_LEFT);
    }

    private function normalizeDocumento(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    private function upper(string $value): string
    {
        return function_exists('mb_strtoupper')
            ? mb_strtoupper(trim($value), 'UTF-8')
            : strtoupper(trim($value));
    }
}
