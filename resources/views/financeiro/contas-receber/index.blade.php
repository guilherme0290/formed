@extends('layouts.financeiro')
@section('title', 'Contas a Receber')
@section('page-container', 'w-full p-0')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canCreate = $isMaster || isset($permissionMap['financeiro.contas-receber.create']);
        $canUpdate = $isMaster || isset($permissionMap['financeiro.contas-receber.update']);
    @endphp
    <div class="w-full px-3 md:px-5 py-4 md:py-5 space-y-8">
        <div class="flex flex-col gap-2">
            <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-indigo-400">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-2xl bg-indigo-500/20 text-pink-100 text-lg">💳</span>
                Contas a Receber
            </div>
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Selecionar vendas em aberto</h1>
                <p class="text-sm text-slate-500 mt-1">Filtre por cliente e período para gerar novas contas</p>
            </div>
        </div>

        @include('financeiro.partials.tabs')

        @if(session('error'))
            <div class="rounded-xl bg-rose-50 text-rose-700 border border-rose-100 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        @endif
        @if(session('success'))
            <div class="rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-100 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5">
            <form method="GET" class="grid gap-4 md:grid-cols-7 items-end">
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Período</label>
                    <div class="flex items-center gap-2">
                        <input type="date" name="data_inicio" value="{{ $filtros['data_inicio'] }}"
                               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                        <span class="text-slate-400">a</span>
                        <input type="date" name="data_fim" value="{{ $filtros['data_fim'] }}"
                               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Cliente</label>
                    <select name="cliente_id" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                        <option value="" class="text-slate-900">Todos</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" class="text-slate-900" @selected($filtros['cliente_id'] == $cliente->id)>
                                {{ $cliente->razao_social ?? $cliente->nome_fantasia ?? 'Cliente' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Status conta</label>
                    <select name="status_conta" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                        <option value="" class="text-slate-900">Todos</option>
                        <option value="FECHADA" class="text-slate-900" @selected($filtros['status_conta'] === 'FECHADA')>Fechada</option>
                        <option value="FATURADA" class="text-slate-900" @selected($filtros['status_conta'] === 'FATURADA')>Faturada</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Tipo de data</label>
                    <div class="flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="tipo_data" value="venda" @checked($filtros['tipo_data'] === 'venda')>
                            Data da venda
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="tipo_data" value="finalizacao" @checked($filtros['tipo_data'] === 'finalizacao')>
                            Data de finalização
                        </label>
                    </div>
                </div>
                <div class="md:col-span-1 flex items-end gap-3">
                    <button class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        Filtrar
                    </button>
                    <a href="{{ route('financeiro.contas-receber') }}" class="text-sm text-slate-500 hover:text-slate-700">Limpar</a>
                </div>
            </form>
        </section>

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Itens em aberto</h2>
                    <p class="text-xs text-slate-500">Vendas em aberto que ainda não foram faturadas</p>
                </div>
                <span class="text-xs text-slate-500">{{ $vendaItens->count() }} itens</span>
            </header>

            <form method="POST" action="{{ route('financeiro.contas-receber.itens') }}">
                @csrf
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold"></th>
                                <th class="px-4 py-3 text-left font-semibold">Tarefa</th>
                                <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                                <th class="px-4 py-3 text-left font-semibold">Serviço</th>
                                <th class="px-4 py-3 text-left font-semibold">Data</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($vendaItens as $item)
                                @php
                                    $venda = $item->venda;
                                    $tarefaId = $venda?->tarefa?->id;
                                    $dataVenda = $venda?->created_at;
                                    $dataFinalizacao = $venda?->tarefa?->finalizado_em;
                                    $dataReferencia = $filtros['tipo_data'] === 'finalizacao' ? $dataFinalizacao : $dataVenda;
                                    $clienteNome = $venda?->cliente?->razao_social ?? 'Cliente';
                                    $servicoNome = $item->servico?->nome ?? $item->descricao_snapshot ?? 'Serviço';
                                    $isAso = strtolower((string) ($item->servico?->nome ?? '')) === 'aso';
                                    $funcionarioNome = $venda?->tarefa?->funcionario?->nome;
                                    if ($isAso && $funcionarioNome) {
                                        $servicoNome = 'ASO - ' . $funcionarioNome;
                                    }
                                @endphp
                                <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100">
                                    <td class="px-4 py-3">
                                        <input type="checkbox"
                                               name="itens[]"
                                               value="{{ $item->id }}"
                                               class="rounded border-slate-300 {{ $canCreate ? '' : 'opacity-60 cursor-not-allowed' }}"
                                               @if(!$canCreate) disabled title="Usu�rio sem permiss�o" @endif>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        {{ $tarefaId ? '#' . $tarefaId : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">{{ $clienteNome }}</td>
                                    <td class="px-4 py-3 text-slate-800">{{ $servicoNome }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $dataReferencia?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                            Em aberto
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                        R$ {{ number_format((float) $item->subtotal_snapshot, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                        Nenhuma venda em aberto encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">Selecione os itens para gerar a conta</span>
                    <button class="px-4 py-2 rounded-xl text-sm font-semibold {{ $canCreate ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                            @if(!$canCreate) disabled title="Usu�rio sem permiss�o" @endif>
                        Criar Contas a Receber
                    </button>
                </div>
            </form>
        </section>

        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <header class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Painel de contas a receber</h2>
                    <p class="text-xs text-slate-500">Gerencie as contas geradas e acompanhe os recebimentos</p>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">Conta</th>
                            <th class="px-4 py-3 text-left font-semibold">Cliente</th>
                            <th class="px-4 py-3 text-left font-semibold">Vencimento</th>
                            <th class="px-4 py-3 text-left font-semibold">Status</th>
                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                            <th class="px-4 py-3 text-right font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($contas as $conta)
                            @php
                                $status = strtoupper((string) $conta->status);
                                $badge = match($status) {
                                    'FATURADA' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'FECHADA' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200',
                                };
                            @endphp
                            <tr class="odd:bg-white even:bg-slate-50 hover:bg-slate-100">
                                <td class="px-4 py-3 text-slate-700">#{{ $conta->id }}</td>
                                <td class="px-4 py-3 text-slate-800">{{ $conta->cliente->razao_social ?? 'Cliente' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional($conta->vencimento)->format('d/m/Y') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $badge }}">
                                        {{ ucfirst(strtolower($status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                    R$ {{ number_format((float) $conta->total, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('financeiro.contas-receber.show', $conta) }}"
                                           class="px-3 py-2 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                            Ver
                                        </a>
                                        <form method="POST" action="{{ route('financeiro.contas-receber.boleto', $conta) }}">
                                            @csrf
                                            <button class="px-3 py-2 rounded-lg text-xs font-semibold {{ $canUpdate ? 'bg-slate-900 text-white hover:bg-slate-800' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}"
                                                    @if(!$canUpdate) disabled title="Usu�rio sem permiss�o" @endif>
                                                Emitir Boleto
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                    Nenhuma conta a receber encontrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t border-slate-100">
                {{ $contas->links() }}
            </div>
        </section>
    </div>
@endsection

