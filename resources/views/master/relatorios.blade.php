@extends('layouts.master')
@php
    $abaTitulo = ($aba_selecionada ?? 'operacional') === 'comercial'
        ? 'Relat&oacute;rios Comerciais'
        : 'Relat&oacute;rios de Produtividade';
@endphp
@section('title', $abaTitulo)

@section('content')
    @php
        $hasProdUsuario = collect($produtividade_top_usuarios['servicos'] ?? [])->sum()
            + collect($produtividade_top_usuarios['propostas'] ?? [])->sum() > 0;
        $dataInicioFmt = !empty($data_inicio) ? \Carbon\Carbon::parse($data_inicio)->format('d/m/Y') : '-';
        $dataFimFmt = !empty($data_fim) ? \Carbon\Carbon::parse($data_fim)->format('d/m/Y') : '-';
    @endphp

    <div class="w-full px-4 md:px-8 py-8 space-y-8 bg-slate-50">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-3xl font-semibold text-slate-900">Relat&oacute;rios de Produtividade</h1>
                <p class="text-sm text-slate-500">An&aacute;lise de produtividade operacional e comercial no per&iacute;odo selecionado</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('master.relatorios.pdf', request()->query()) }}"
                   class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 shadow-sm">
                    Exportar PDF
                </a>
                <a href="{{ route('master.dashboard') }}"
                   class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50 shadow-sm">
                    Voltar ao painel
                </a>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('master.relatorios', array_merge(request()->query(), ['aba' => 'operacional'])) }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold border {{ ($aba_selecionada ?? 'operacional') === 'operacional' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200' }}">
                Operacional
            </a>
            <a href="{{ route('master.relatorios', array_merge(request()->query(), ['aba' => 'comercial'])) }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold border {{ ($aba_selecionada ?? 'operacional') === 'comercial' ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-700 border-slate-200' }}">
                Comercial
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 space-y-6">
            <form method="GET" class="grid gap-3 md:grid-cols-6 items-end">
                <input type="hidden" name="aba" value="{{ $aba_selecionada ?? 'operacional' }}">
                <div>
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Data in&iacute;cio</label>
                    <input type="date" name="data_inicio"
                           value="{{ $data_inicio ?? '' }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Data fim</label>
                    <input type="date" name="data_fim"
                           value="{{ $data_fim ?? '' }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Usu&aacute;rio</label>
                    <select name="usuario"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                        <option value="todos" @selected(($usuario_selecionado ?? 'todos') === 'todos')>
                            Todos os usu&aacute;rios
                        </option>
                        @foreach(($usuarios_disponiveis ?? []) as $usuario)
                            <option value="{{ $usuario->id }}"
                                @selected(($usuario_selecionado ?? 'todos') == $usuario->id)>
                                {{ $usuario->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(($aba_selecionada ?? 'operacional') === 'operacional')
                    <div class="md:col-span-2">
                        <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Servi&ccedil;o</label>
                        <select name="servico"
                                class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                            <option value="todos" @selected(($servico_selecionado ?? 'todos') === 'todos')>
                                Todos os servi&ccedil;os
                            </option>
                            @foreach(($servicos_disponiveis ?? []) as $servico)
                                <option value="{{ $servico->id }}"
                                    @selected(($servico_selecionado ?? 'todos') == $servico->id)>
                                    {{ $servico->nome }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="status_servicos" value="{{ $status_servicos_selecionado ?? 'concluido' }}">
                @else
                    <div class="md:col-span-2">
                        <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Status proposta</label>
                        <select name="status_proposta"
                                class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                            @foreach(($status_proposta_opcoes ?? []) as $status)
                                <option value="{{ $status }}"
                                    @selected(($status_proposta_selecionado ?? 'FECHADA') === $status)>
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="md:col-span-6 flex flex-wrap items-center justify-between gap-3">
                    <div class="text-xs text-slate-500">Per&iacute;odo analisado: {{ $dataInicioFmt }} -> {{ $dataFimFmt }}</div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('master.relatorios', ['aba' => $aba_selecionada ?? 'operacional']) }}"
                           class="h-[44px] px-4 rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 inline-flex items-center gap-2">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18"></path>
                                <path d="M8 6V4h8v2"></path>
                                <path d="M6 6l1 14h10l1-14"></path>
                            </svg>
                            Limpar filtros
                        </a>
                        <button type="submit"
                                class="h-[44px] px-5 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800 inline-flex items-center gap-2">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="7"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            Filtrar
                        </button>
                    </div>
                </div>
            </form>

            @if(($aba_selecionada ?? 'operacional') === 'operacional')
                @php
                    $totalPeriodo = ($resumo_periodo['pendentes'] ?? 0) + ($resumo_periodo['finalizadas'] ?? 0) + ($resumo_periodo['atrasadas'] ?? 0);
                    $taxaConclusao = $totalPeriodo > 0
                        ? round((($resumo_periodo['finalizadas'] ?? 0) / $totalPeriodo) * 100)
                        : 0;
                    $variacaoPeriodo = $variacao_periodo ?? [];
                    $varPendentes = (int) ($variacaoPeriodo['pendentes'] ?? 0);
                    $varFinalizadas = (int) ($variacaoPeriodo['finalizadas'] ?? 0);
                    $varAtrasadas = (int) ($variacaoPeriodo['atrasadas'] ?? 0);
                    $varOperacional = (int) ($variacaoPeriodo['operacional_ativo'] ?? 0);
                    $formatVar = fn (int $valor) => ($valor > 0 ? '+' : '') . $valor . '% em rela&ccedil;&atilde;o ao per&iacute;odo anterior';
                    $varClass = fn (int $valor) => $valor > 0
                        ? 'text-emerald-700'
                        : ($valor < 0 ? 'text-rose-700' : 'text-slate-600');
                @endphp
                <div class="grid gap-4 md:grid-cols-5">
                    <div class="rounded-2xl border border-sky-200 bg-sky-50/60 px-4 py-4 shadow-sm">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-200 text-sky-800 text-[11px] font-bold">T</span>
                            Total de tarefas
                        </div>
                        <div class="text-2xl font-semibold text-slate-900 mt-2">
                            {{ $totalPeriodo }} tarefas
                        </div>
                        <div class="text-[11px] text-slate-600 mt-1">Per&iacute;odo atual</div>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/60 px-4 py-4 shadow-sm">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-200 text-amber-800 text-[11px] font-bold">P</span>
                            Pendentes
                        </div>
                        <div class="text-2xl font-semibold text-slate-900 mt-2">
                            {{ $resumo_periodo['pendentes'] ?? 0 }} tarefas
                        </div>
                        <div class="text-[11px] mt-1 {{ $varClass($varPendentes) }}">{{ $formatVar($varPendentes) }}</div>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 px-4 py-4 shadow-sm">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-200 text-emerald-800 text-[11px] font-bold">F</span>
                            Finalizadas
                        </div>
                        <div class="text-2xl font-semibold text-slate-900 mt-2">
                            {{ $resumo_periodo['finalizadas'] ?? 0 }} tarefas
                        </div>
                        <div class="text-[11px] mt-1 {{ $varClass($varFinalizadas) }}">{{ $formatVar($varFinalizadas) }}</div>
                    </div>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50/60 px-4 py-4 shadow-sm">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-rose-200 text-rose-800 text-[11px] font-bold">A</span>
                            Atrasadas
                        </div>
                        <div class="text-2xl font-semibold text-slate-900 mt-2">
                            {{ $resumo_periodo['atrasadas'] ?? 0 }} tarefas
                        </div>
                        <div class="text-[11px] mt-1 {{ $varClass($varAtrasadas) }}">{{ $formatVar($varAtrasadas) }}</div>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50/60 px-4 py-4 shadow-sm">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-200 text-sky-800 text-[11px] font-bold">O</span>
                            Operacional ativo
                        </div>
                        <div class="text-2xl font-semibold text-slate-900 mt-2">
                            {{ $operacional_ativo ?? 0 }} tarefas
                        </div>
                        <div class="text-[11px] mt-1 {{ $varClass($varOperacional) }}">{{ $formatVar($varOperacional) }}</div>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4">
                            <div class="text-sm font-semibold text-slate-900 mb-3">Ranking operacional</div>
                            @if($hasProdUsuario)
                                <div class="overflow-x-auto">
                                    <div id="chartProdutividadeUsuarioWrap" class="h-80 min-w-[600px]">
                                        <canvas id="chartProdutividadeUsuario"></canvas>
                                    </div>
                                </div>
                            @else
                                <div class="text-sm text-slate-500 py-8 text-center">Sem dados no per&iacute;odo.</div>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 text-sm font-semibold text-slate-900">
                                Produtividade por usu&aacute;rio (detalhado)
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Usu&aacute;rio</th>
                                            <th class="px-4 py-3 text-left font-semibold">Finalizadas</th>
                                            <th class="px-4 py-3 text-left font-semibold">Pendentes</th>
                                            <th class="px-4 py-3 text-left font-semibold">Atrasadas</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach(($produtividade_top_usuarios['labels'] ?? []) as $index => $label)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-3 text-slate-700">{{ $label }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $produtividade_top_usuarios['servicos'][$index] ?? 0 }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $produtividade_top_usuarios['pendentes'][$index] ?? 0 }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $produtividade_top_usuarios['atrasadas'][$index] ?? 0 }}</td>
                                            </tr>
                                        @endforeach
                                        @if(empty($produtividade_top_usuarios['labels']))
                                            <tr>
                                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                                                    Nenhum usu&aacute;rio com servi&ccedil;os finalizados no per&iacute;odo.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                            @php
                                $statusServicosLabel = collect($status_servicos_opcoes ?? [])
                                    ->firstWhere('value', $status_servicos_selecionado ?? 'concluido')['label']
                                    ?? 'Conclu&iacute;do';
                                $servicosTotalSum = collect($servicos_resumo ?? [])->sum('total') ?: 1;
                            @endphp
                            <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm font-semibold text-slate-900">Servi&ccedil;os Finalizados</div>
                                <form method="GET" class="flex items-center gap-2">
                                    @foreach(request()->query() as $key => $value)
                                        @if($key !== 'status_servicos')
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach
                                    <label class="text-xs font-bold text-slate-700 whitespace-nowrap">Status</label>
                                    <select name="status_servicos"
                                            class="rounded-lg border border-slate-200 text-xs px-2 py-1 h-[34px]"
                                            onchange="this.form.submit()">
                                        @foreach(($status_servicos_opcoes ?? []) as $status)
                                            <option value="{{ $status['value'] }}"
                                                @selected(($status_servicos_selecionado ?? 'concluido') === $status['value'])>
                                                {{ $status['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold">Servi&ccedil;o</th>
                                            <th class="px-4 py-3 text-left font-semibold">Total ({{ $statusServicosLabel }})</th>
                                            <th class="px-4 py-3 text-left font-semibold">Percentual</th>
                                            <th class="px-4 py-3 text-left font-semibold">Progresso</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse(($servicos_resumo ?? []) as $row)
                                            @php
                                                $percentual = $servicosTotalSum > 0 ? round(($row->total / $servicosTotalSum) * 100) : 0;
                                            @endphp
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-4 py-3 text-slate-700">{{ $row->servico_nome }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $row->total }}</td>
                                                <td class="px-4 py-3 text-slate-700">{{ $percentual }}%</td>
                                                <td class="px-4 py-3">
                                                    <div class="w-full bg-slate-100 rounded-full h-2">
                                                        <div class="h-2 rounded-full bg-emerald-400" style="width: {{ $percentual }}%;"></div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-4 py-6 text-center text-slate-500">
                                                    Nenhum servi&ccedil;o finalizado para os filtros selecionados.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                @php
                    $usuariosAtivos = count($produtividade_usuarios['labels'] ?? []);
                    $totalValor = (float) ($produtividade_valor_total ?? 0);
                    $totalPropostas = (int) ($produtividade_setor['comercial'] ?? 0);
                    $ticketMedio = (float) ($ticket_medio ?? 0);
                    $labelsComercial = $produtividade_usuarios['labels'] ?? [];
                    $valoresComercial = $produtividade_usuarios['propostas_valor'] ?? [];
                    $qtdComercial = $produtividade_usuarios['propostas'] ?? [];
                    $maxValor = !empty($valoresComercial) ? max($valoresComercial) : 0;
                    $idxMax = $maxValor > 0 ? array_search($maxValor, $valoresComercial, true) : null;
                    $topNome = ($idxMax !== null && isset($labelsComercial[$idxMax])) ? $labelsComercial[$idxMax] : null;
                    $shareTop = $totalValor > 0 && $maxValor > 0 ? round(($maxValor / $totalValor) * 100) : 0;
                    $ticketTop = 0;
                    if ($idxMax !== null && isset($qtdComercial[$idxMax]) && (int) $qtdComercial[$idxMax] > 0) {
                        $ticketTop = $maxValor / (int) $qtdComercial[$idxMax];
                    }
                    $ticketDiff = $ticketMedio > 0 && $ticketTop > 0 ? round((($ticketTop - $ticketMedio) / $ticketMedio) * 100) : 0;
                @endphp

                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-slate-600">i</span>
                    Per&iacute;odo analisado: {{ $dataInicioFmt }} &rarr; {{ $dataFimFmt }}
                </div>

                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/60 px-4 py-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-indigo-200 text-indigo-800 text-[11px] font-bold">P</span>
                            Propostas fechadas
                        </div>
                        <div class="text-2xl font-semibold text-slate-900 mt-2">{{ $totalPropostas }}</div>
                        <div class="text-[11px] text-emerald-700 mt-1">+0% em rela&ccedil;&atilde;o ao per&iacute;odo anterior</div>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 px-4 py-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-emerald-200 text-emerald-800 text-[11px] font-bold">R$</span>
                            Valor total
                        </div>
                        <div class="text-2xl font-semibold text-slate-900 mt-2">
                            R$ {{ number_format($totalValor, 2, ',', '.') }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">Valor em reais</div>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/60 px-4 py-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-200 text-amber-800 text-[11px] font-bold">T</span>
                            Ticket m&eacute;dio
                        </div>
                        <div class="text-2xl font-semibold text-slate-900 mt-2">
                            R$ {{ number_format($ticketMedio, 2, ',', '.') }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">Valor m&eacute;dio por proposta</div>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50/60 px-4 py-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-900 uppercase">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-sky-200 text-sky-800 text-[11px] font-bold">U</span>
                            Usu&aacute;rios ativos
                        </div>
                        <div class="text-2xl font-semibold text-slate-900 mt-2">{{ $usuariosAtivos }}</div>
                        <div class="text-[11px] text-emerald-700 mt-1">+0% em rela&ccedil;&atilde;o ao per&iacute;odo anterior</div>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4">
                        <div class="text-sm font-semibold text-slate-900 mb-1">Propostas fechadas por vendedor</div>
                        <div class="text-xs text-slate-500 mb-3">Per&iacute;odo: {{ $dataInicioFmt }} a {{ $dataFimFmt }}</div>
                        @if($hasProdUsuario)
                            <div class="overflow-x-auto">
                                <div id="chartPropostasVendedoresWrap" class="h-72 min-w-[600px]">
                                    <canvas id="chartPropostasVendedores"></canvas>
                                </div>
                            </div>
                        @else
                            <div class="text-sm text-slate-500 py-8 text-center">Sem dados no per&iacute;odo.</div>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 space-y-4">
                        <div>
                            <div class="text-sm font-semibold text-slate-900 mb-3">Propostas fechadas por vendedor</div>
                            <ul class="text-sm text-slate-600 space-y-2">
                                @foreach(($labelsComercial ?? []) as $idx => $nome)
                                    @php
                                        $valor = (float) (($valoresComercial ?? [])[$idx] ?? 0);
                                        $qtd = (int) (($qtdComercial ?? [])[$idx] ?? 0);
                                        $share = $totalValor > 0 ? round(($valor / $totalValor) * 100) : 0;
                                        $ticketUsuario = $qtd > 0 ? ($valor / $qtd) : 0;
                                        $ticketVar = $ticketMedio > 0 && $ticketUsuario > 0
                                            ? round((($ticketUsuario - $ticketMedio) / $ticketMedio) * 100)
                                            : 0;
                                    @endphp
                                    @if($qtd > 0)
                                        <li>
                                            ✓ {{ $nome }} fechou {{ $qtd }} proposta{{ $qtd > 1 ? 's' : '' }}
                                            (R$ {{ number_format($valor, 2, ',', '.') }}), participa&ccedil;&atilde;o de {{ $share }}%.
                                            Ticket m&eacute;dio {{ $ticketVar >= 0 ? 'acima' : 'abaixo' }} da m&eacute;dia em {{ abs($ticketVar) }}%.
                                        </li>
                                    @else
                                        <li>✓ {{ $nome }} n&atilde;o fechou propostas no per&iacute;odo.</li>
                                    @endif
                                @endforeach
                                @if(empty($labelsComercial))
                                    <li>✓ N&atilde;o h&aacute; vendedores no per&iacute;odo selecionado.</li>
                                @endif
                            </ul>
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-slate-900 mb-2">Valores por usu&aacute;rio</div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-slate-50 text-slate-600">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold">Usu&aacute;rio</th>
                                            <th class="px-3 py-2 text-left font-semibold">Propostas</th>
                                            <th class="px-3 py-2 text-left font-semibold">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach(($produtividade_usuarios['labels'] ?? []) as $index => $label)
                                            @php
                                                $qtd = (int) (($produtividade_usuarios['propostas'] ?? [])[$index] ?? 0);
                                                $valor = (float) (($produtividade_usuarios['propostas_valor'] ?? [])[$index] ?? 0);
                                            @endphp
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2 text-slate-700">{{ $label }}</td>
                                                <td class="px-3 py-2 text-slate-700">{{ $qtd }}</td>
                                                <td class="px-3 py-2 text-slate-700">R$ {{ number_format($valor, 2, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                        @if(empty($produtividade_usuarios['labels']))
                                            <tr>
                                                <td colspan="3" class="px-3 py-4 text-center text-slate-500">Sem dados no per&iacute;odo.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="text-xs text-slate-400">
            Relat&oacute;rio gerado em 27/01/2026 por Administrador Master
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const usuarioLabels = @json(($produtividade_usuarios['labels'] ?? []));
        const usuarioServicos = @json(($produtividade_usuarios['servicos'] ?? []));
        const usuarioPendentes = @json(($produtividade_usuarios['pendentes'] ?? []));
        const usuarioAtrasadas = @json(($produtividade_usuarios['atrasadas'] ?? []));
        const vendedoresLabels = @json(($produtividade_usuarios['labels'] ?? []));
        const vendedoresPropostas = @json(($produtividade_usuarios['propostas_valor'] ?? []));

        const elProdUsuario = document.getElementById('chartProdutividadeUsuario');
        if (elProdUsuario && usuarioLabels.length && (usuarioServicos.reduce((a,b)=>a+b,0) + usuarioPendentes.reduce((a,b)=>a+b,0) + usuarioAtrasadas.reduce((a,b)=>a+b,0)) > 0) {
            const wrap = document.getElementById('chartProdutividadeUsuarioWrap');
            if (wrap) {
                const minWidth = Math.max(600, usuarioLabels.length * 90);
                wrap.style.width = `${minWidth}px`;
            }
            const totalLabelPlugin = {
                id: 'totalLabelPlugin',
                afterDatasetsDraw(chart) {
                    const { ctx } = chart;
                    const meta0 = chart.getDatasetMeta(0);
                    const meta1 = chart.getDatasetMeta(1);
                    const meta2 = chart.getDatasetMeta(2);
                    ctx.save();
                    ctx.fillStyle = '#111827';
                    ctx.font = '12px sans-serif';
                    usuarioLabels.forEach((_, i) => {
                        const bar0 = meta0.data[i];
                        const bar1 = meta1.data[i];
                        const bar2 = meta2.data[i];
                        if (!bar0 && !bar1 && !bar2) return;
                        const total = (usuarioPendentes[i] || 0) + (usuarioServicos[i] || 0) + (usuarioAtrasadas[i] || 0);
                        if (!total) return;
                        const x = (bar2 || bar1 || bar0).x;
                        const ys = [bar0?.y, bar1?.y, bar2?.y].filter((v) => typeof v === 'number');
                        const y = Math.min(...ys) - 6;
                        ctx.textAlign = 'center';
                        ctx.fillText(String(total), x, y);
                    });
                    ctx.restore();
                }
            };
            new Chart(elProdUsuario, {
                type: 'bar',
                data: {
                    labels: usuarioLabels,
                    datasets: [
                        { label: 'Pendentes', data: usuarioPendentes, backgroundColor: '#fbbf24' },
                        { label: 'Finalizadas', data: usuarioServicos, backgroundColor: '#34d399' },
                        { label: 'Atrasadas', data: usuarioAtrasadas, backgroundColor: '#f97316' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 16 } },
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true }
                    },
                    plugins: { legend: { position: 'bottom' } }
                },
                plugins: [totalLabelPlugin]
            });
        }

        const elVendedores = document.getElementById('chartPropostasVendedores');
        if (elVendedores && vendedoresLabels.length) {
            const wrap = document.getElementById('chartPropostasVendedoresWrap');
            if (wrap) {
                const minWidth = Math.max(600, vendedoresLabels.length * 90);
                wrap.style.width = `${minWidth}px`;
            }
            const formatCurrency = (value) => {
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value || 0);
            };
            new Chart(elVendedores, {
                type: 'bar',
                data: {
                    labels: vendedoresLabels,
                    datasets: [{
                        label: 'Valor das propostas',
                        data: vendedoresPropostas,
                        backgroundColor: '#6366f1',
                        barThickness: 28,
                        maxBarThickness: 36,
                        categoryPercentage: 0.6,
                        barPercentage: 0.7,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => formatCurrency(value)
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => formatCurrency(ctx.parsed.y)
                            }
                        }
                    }
                }
            });
        }
    </script>
@endpush
