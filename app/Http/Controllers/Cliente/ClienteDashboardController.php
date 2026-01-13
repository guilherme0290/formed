<?php

namespace App\Http\Controllers\Cliente;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteContrato;
use App\Models\ClienteTabelaPreco;
use App\Models\ClienteTabelaPrecoItem;
use App\Models\ContaReceberBaixa;
use App\Models\ContaReceberItem;
use App\Models\Servico;
use App\Models\Venda;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ClienteDashboardController extends Controller
{
    /**
     * Tela inicial do Portal do Cliente.
     */
    public function index(Request $request)
    {
        $contexto = $this->resolverCliente($request);
        if ($contexto instanceof RedirectResponse) {
            return $contexto;
        }

        [$user, $cliente] = $contexto;

        [$contratoAtivo, $servicosContrato, $servicosIds] = $this->servicosLiberadosPorContrato($cliente);

        $precos = $this->precosPorContrato($contratoAtivo, $servicosIds);
        $tabela = $this->tabelaAtiva($cliente);
        if (empty(array_filter($precos))) {
            $precos = $this->precosPorServico($cliente, $tabela);
        }
        $temTabela = (bool) $tabela;
        $faturaTotal = $this->faturaTotal($cliente);
        $vendedorTelefone = $this->telefoneVendedor($cliente, $contratoAtivo);

        return view('clientes.dashboard', [
            'user'         => $user,
            'cliente'      => $cliente,
            'temTabela'    => $temTabela,
            'precos'       => $precos,
            'faturaTotal'  => $faturaTotal,
            'contratoAtivo' => $contratoAtivo,
            'servicosContrato' => $servicosContrato,
            'servicosIds' => $servicosIds,
            'vendedorTelefone' => $vendedorTelefone,
        ]);
    }

    /**
     * Tela de detalhes de faturas/serviços faturados.
     */
    public function faturas(Request $request)
    {
        $contexto = $this->resolverCliente($request);
        if ($contexto instanceof RedirectResponse) {
            return $contexto;
        }

        [$user, $cliente] = $contexto;

        [$contratoAtivo, $servicosContrato, $servicosIds] = $this->servicosLiberadosPorContrato($cliente);
        $precos = $this->precosPorContrato($contratoAtivo, $servicosIds);
        $tabela = $this->tabelaAtiva($cliente);
        if (empty(array_filter($precos))) {
            $precos = $this->precosPorServico($cliente, $tabela);
        }
        $temTabela = (bool) $tabela;
        $faturaTotal = $this->faturaTotal($cliente);

        $dataInicio = $request->input('data_inicio');
        $dataFim = $request->input('data_fim');
        $status = $request->input('status');

        $contaQuery = DB::table('contas_receber_itens as cri')
            ->leftJoin('servicos as s', 's.id', '=', 'cri.servico_id')
            ->leftJoin('venda_itens as vi', 'vi.id', '=', 'cri.venda_item_id')
            ->where('cri.empresa_id', $cliente->empresa_id)
            ->where('cri.cliente_id', $cliente->id)
            ->where('cri.status', '!=', 'CANCELADO')
            ->selectRaw("'conta' as origem")
            ->selectRaw('cri.id as ref_id')
            ->selectRaw('COALESCE(s.nome, cri.descricao, vi.descricao_snapshot, "Serviço") as servico')
            ->selectRaw('cri.data_realizacao as data_realizacao')
            ->selectRaw('cri.vencimento as vencimento')
            ->selectRaw('cri.status as status')
            ->selectRaw('cri.valor as valor');

        if ($status) {
            $contaQuery->where('cri.status', strtoupper((string) $status));
        }
        if ($dataInicio) {
            $contaQuery->whereDate('cri.data_realizacao', '>=', $dataInicio);
        }
        if ($dataFim) {
            $contaQuery->whereDate('cri.data_realizacao', '<=', $dataFim);
        }

        $vendaQuery = DB::table('venda_itens as vi')
            ->join('vendas as v', 'v.id', '=', 'vi.venda_id')
            ->leftJoin('tarefas as t', 't.id', '=', 'v.tarefa_id')
            ->leftJoin('servicos as s', 's.id', '=', 'vi.servico_id')
            ->where('v.empresa_id', $cliente->empresa_id)
            ->where('v.cliente_id', $cliente->id)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('contas_receber_itens as cri')
                    ->whereColumn('cri.venda_item_id', 'vi.id')
                    ->where('cri.status', '!=', 'CANCELADO');
            })
            ->selectRaw("'venda' as origem")
            ->selectRaw('vi.id as ref_id')
            ->selectRaw('COALESCE(s.nome, vi.descricao_snapshot, "Serviço") as servico')
            ->selectRaw('COALESCE(DATE(t.finalizado_em), DATE(v.created_at)) as data_realizacao')
            ->selectRaw('NULL as vencimento')
            ->selectRaw("'ABERTO' as status")
            ->selectRaw('vi.subtotal_snapshot as valor');

        if ($status) {
            if (strtoupper((string) $status) === 'BAIXADO') {
                $vendaQuery->whereRaw('1=0');
            }
        }
        if ($dataInicio) {
            $vendaQuery->whereDate(DB::raw('COALESCE(t.finalizado_em, v.created_at)'), '>=', $dataInicio);
        }
        if ($dataFim) {
            $vendaQuery->whereDate(DB::raw('COALESCE(t.finalizado_em, v.created_at)'), '<=', $dataFim);
        }

        $union = $contaQuery->unionAll($vendaQuery);

        $itens = DB::query()
            ->fromSub($union, 'reg')
            ->orderByDesc('data_realizacao')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.faturas.index', [
            'user'         => $user,
            'cliente'      => $cliente,
            'temTabela'    => $temTabela,
            'precos'       => $precos,
            'faturaTotal'  => $faturaTotal,
            'itens'        => $itens,
            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'status' => $status,
            ],
            'contratoAtivo' => $contratoAtivo,
            'servicosContrato' => $servicosContrato,
            'servicosIds' => $servicosIds,
        ]);
    }

    /**
     * Resolve o cliente do portal com base na sessão; redireciona pro login em caso de falha.
     */
    private function resolverCliente(Request $request): RedirectResponse|array
    {
        $user = $request->user();

        if (!$user || !$user->id) {
            return redirect()
                ->route('login', ['redirect' => 'cliente']);
        }

        $clienteId = (int) $request->session()->get('portal_cliente_id');

        if ($clienteId <= 0) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Nenhum cliente selecionado. Faça login novamente pelo portal do cliente.');
        }

        $cliente = Cliente::with('vendedor')->find($clienteId);

        if (!$cliente) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Cliente inválido. Acesse novamente pelo portal do cliente.');
        }

        return [$user, $cliente];
    }

    private function tabelaAtiva(Cliente $cliente): ?ClienteTabelaPreco
    {
        return ClienteTabelaPreco::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->where('ativa', true)
            ->first();
    }

    private function servicoIdPorTipo(Cliente $cliente, string $tipo): ?int
    {
        if (mb_strtolower($tipo) === 'aso') {
            return app(\App\Services\AsoGheService::class)
                ->resolveServicoAsoId($cliente->id, $cliente->empresa_id);
        }

        return Servico::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->whereRaw('LOWER(tipo) = ?', [mb_strtolower($tipo)])
            ->value('id');
    }

    private function precoDoServico(?ClienteTabelaPreco $tabela, ?int $servicoId): ?float
    {
        if (!$tabela || !$servicoId) {
            return null;
        }

        $item = ClienteTabelaPrecoItem::query()
            ->where('cliente_tabela_preco_id', $tabela->id)
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->orderBy('descricao')
            ->first();

        return $item?->valor_unitario ? (float) $item->valor_unitario : null;
    }

    private function precosPorServico(Cliente $cliente, ?ClienteTabelaPreco $tabela): array
    {
        $servicos = [
            'aso'          => $this->servicoIdPorTipo($cliente, 'aso'),
            'pgr'          => $this->servicoIdPorTipo($cliente, 'pgr'),
            'pcmso'        => $this->servicoIdPorTipo($cliente, 'pcmso'),
            'ltcat'        => $this->servicoIdPorTipo($cliente, 'ltcat'),
            'apr'          => $this->servicoIdPorTipo($cliente, 'apr'),
            'treinamentos' => $this->servicoIdPorTipo($cliente, 'treinamento'),
        ];

        $precos = [];
        foreach ($servicos as $slug => $servicoId) {
            $precos[$slug] = $this->precoDoServico($tabela, $servicoId);
        }

        return $precos;
    }

    private function faturaTotal(Cliente $cliente): float
    {
        $contasAberto = (float) ContaReceberItem::query()
            ->where('contas_receber_itens.empresa_id', $cliente->empresa_id)
            ->where('contas_receber_itens.cliente_id', $cliente->id)
            ->where('contas_receber_itens.status', '!=', 'CANCELADO')
            ->selectRaw('COALESCE(SUM(GREATEST(contas_receber_itens.valor - COALESCE(baixas.total_baixado, 0), 0)), 0) as total')
            ->leftJoinSub(
                ContaReceberBaixa::query()
                    ->selectRaw('conta_receber_item_id, SUM(valor) as total_baixado')
                    ->groupBy('conta_receber_item_id'),
                'baixas',
                fn ($join) => $join->on('contas_receber_itens.id', '=', 'baixas.conta_receber_item_id')
            )
            ->value('total');

        $vendasSemConta = (float) Venda::query()
            ->where('cliente_id', $cliente->id)
            ->whereHas('tarefa.coluna', function ($q) {
                $q->where('finaliza', true);
            })
            ->whereDoesntHave('itens.contasReceberItens', function ($q) {
                $q->where('status', '!=', 'CANCELADO');
            })
            ->sum('total');

        return $contasAberto + $vendasSemConta;
    }

    private function contratoAtivo(Cliente $cliente): ?ClienteContrato
    {
        $hoje = now()->toDateString();

        return ClienteContrato::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->where('status', 'ATIVO')
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_inicio')->orWhereDate('vigencia_inicio', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_fim')->orWhereDate('vigencia_fim', '>=', $hoje);
            })
            ->with('itens')
            ->first();
    }

    private function servicosIdsContrato(Cliente $cliente): array
    {
        $servicosIds = [
            'aso' => app(\App\Services\AsoGheService::class)
                ->resolveServicoAsoId($cliente->id, $cliente->empresa_id),
        ];

        $tipos = [
            'pgr' => ['pgr', 'pgr'],
            'pcmso' => ['pcmso', 'pcmso'],
            'ltcat' => ['ltcat', 'ltcat'],
            'apr' => ['apr', 'apr'],
            'treinamentos' => ['treinamento', 'treinamentos nrs'],
        ];

        foreach ($tipos as $slug => $variants) {
            $variants = array_map(fn ($v) => mb_strtolower($v), $variants);
            $id = Servico::query()
                ->where('empresa_id', $cliente->empresa_id)
                ->where(function ($q) use ($variants) {
                    foreach ($variants as $v) {
                        $q->orWhereRaw('LOWER(tipo) = ?', [$v])
                          ->orWhereRaw('LOWER(nome) = ?', [$v]);
                    }
                })
                ->value('id');
            $servicosIds[$slug] = $id;
        }

        return $servicosIds;
    }

    private function servicosLiberadosPorContrato(Cliente $cliente): array
    {
        $hoje = now()->toDateString();

        $contratoAtivo = ClienteContrato::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->where('status', 'ATIVO')
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_inicio')->orWhereDate('vigencia_inicio', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_fim')->orWhereDate('vigencia_fim', '>=', $hoje);
            })
            ->with('itens')
            ->first();

        $servicosContrato = $contratoAtivo
            ? $contratoAtivo->itens->pluck('servico_id')->filter()->unique()->values()->all()
            : [];

        $asoService = app(\App\Services\AsoGheService::class);
        $asoServicoId = $asoService->resolveServicoAsoIdFromContrato($contratoAtivo);
        $tiposAsoPermitidos = $asoService->resolveTiposAsoContrato($contratoAtivo);
        if (empty($tiposAsoPermitidos)) {
            $asoServicoId = null;
        }

        $tipos = [
            'pgr' => ['pgr', 'pgr'],
            'pcmso' => ['pcmso', 'pcmso'],
            'ltcat' => ['ltcat', 'ltcat'],
            'ltip' => ['ltip', 'ltip'],
            'apr' => ['apr', 'apr'],
            'pae' => ['pae', 'pae'],
            'treinamentos' => ['treinamento', 'treinamentos nrs'],
        ];

        $servicosIds = [
            'aso' => $asoServicoId ? (int) $asoServicoId : null,
        ];
        foreach ($tipos as $slug => $variants) {
            $variants = array_map(fn ($v) => mb_strtolower($v), $variants);
            $id = Servico::query()
                ->where('empresa_id', $cliente->empresa_id)
                ->where(function ($q) use ($variants) {
                    foreach ($variants as $v) {
                        $q->orWhereRaw('LOWER(tipo) = ?', [$v])
                          ->orWhereRaw('LOWER(nome) = ?', [$v]);
                    }
                })
                ->value('id');
            $servicosIds[$slug] = $id;
        }

        return [$contratoAtivo, $servicosContrato, $servicosIds];
    }

    private function precosPorContrato(?ClienteContrato $contrato, array $servicosIds): array
    {
        $precos = [];
        if (!$contrato) {
            foreach (array_keys($servicosIds) as $slug) {
                $precos[$slug] = null;
            }
            return $precos;
        }

        foreach ($servicosIds as $slug => $servicoId) {
            if (!$servicoId) {
                $precos[$slug] = null;
                continue;
            }

            $item = $contrato->itens()
                ->where('servico_id', $servicoId)
                ->where('ativo', true)
                ->orderBy('descricao_snapshot')
                ->first();

            $precos[$slug] = $item?->preco_unitario_snapshot ? (float) $item->preco_unitario_snapshot : null;
        }

        return $precos;
    }

    private function telefoneVendedor(Cliente $cliente, ?ClienteContrato $contratoAtivo): string
    {
        $vendedorId = (int) ($contratoAtivo?->vendedor_id ?? 0);
        $vendedor = $vendedorId > 0 ? \App\Models\User::find($vendedorId) : null;
        $telefone = preg_replace('/\D+/', '', $vendedor?->telefone ?? '');
        if ($telefone !== '') {
            return $telefone;
        }

        return preg_replace('/\D+/', '', optional($cliente->vendedor)->telefone ?? '');
    }
}
