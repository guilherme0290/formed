@extends('layouts.operacional')

@section('title', 'Nova Tarefa - Selecionar Servico')

@section('content')
    @php
        $user = auth()->user();
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $origem = request()->query('origem');
        $estaNoPortalCliente = session('portal_cliente_id') || $origem === 'cliente';
        $rotaVoltar = $estaNoPortalCliente ? route('cliente.dashboard') : route('operacional.kanban');
        $rotaListaClientes = route('operacional.kanban.aso.clientes', $origem ? ['origem' => $origem] : []);
        $temContratoAtivo = (bool) ($contratoAtivo ?? false);
        $servicosContrato = $servicosContrato ?? [];
        $servicosIds = $servicosIds ?? [];
    @endphp

    <div class="max-w-6xl mx-auto px-6 py-8">

        {{-- Voltar --}}
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ $rotaListaClientes }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>&larr;</span>
                <span>Voltar</span>
            </a>
            <a href="{{ $rotaVoltar }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                <span>&larr;</span>
                <span>Voltar ao Painel</span>
            </a>
        </div>

        {{-- Card principal --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            {{-- Cabeçalho --}}
            <div class="px-5 md:px-6 py-4 md:py-5
            flex flex-col md:flex-row md:items-center justify-between gap-4
            bg-sky-50 border-b border-sky-100">

                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[color:var(--color-brand-azul)]/10
                    flex items-center justify-center text-xl">
                        &#x1F9E9;
                    </div>

                    <div>
                        <p class="text-[11px] md:text-xs uppercase tracking-wide text-[color:var(--color-brand-azul)]/80">
                            Operacional &bull; Formed
                        </p>
                        <h1 class="text-base md:text-xl font-semibold text-slate-900 leading-snug">
                            Selecione o serviço para este cliente
                        </h1>
                    </div>
                </div>

                <div class="md:text-right space-y-1">
                    <p class="text-[11px] md:text-xs text-slate-500">
                        Empresa selecionada
                    </p>

                    <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full
                    bg-[color:var(--color-brand-azul)]/5 border border-[color:var(--color-brand-azul)]/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-[color:var(--color-brand-azul)]"></span>
                        <p class="text-xs md:text-sm font-semibold text-slate-800 max-w-xs truncate">
                            {{ $cliente->razao_social ?? $cliente->nome_fantasia }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Conteúdo --}}
            <div class="px-6 py-6">
                @unless($temContratoAtivo)
                    <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                        Este cliente não possui contrato ativo ou vigência válida. Selecione outro cliente ou acione o Comercial para ajustar o contrato.
                    </div>
                @endunless

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                    {{-- helper inline --}}
                    @php
                        $bloqueadoMsg = 'Bloqueado: serviço não consta na tabela de preço vigente do contrato.';
                        $semPermissaoMsg = 'Usuário sem permissão para criar este serviço.';
                    @endphp

                    {{-- ASO --}}
                    @php $asoPermitido = $temContratoAtivo && in_array($servicosIds['aso'] ?? null, $servicosContrato) && ($isMaster || isset($permissionMap['operacional.aso.create'])); @endphp
                    <a @if($asoPermitido) href="{{ route('operacional.kanban.aso.create', ['cliente' => $cliente, 'origem'  => $origem]) }}" @endif
                       class="group rounded-2xl border border-sky-200 bg-gradient-to-br from-sky-50 to-sky-100 p-4
          flex flex-col justify-between {{ $asoPermitido ? 'hover:from-sky-100 hover:to-sky-200 hover:border-sky-300 hover:shadow-md' : 'opacity-60 cursor-not-allowed' }} transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-sky-500 flex items-center justify-center text-white text-xl mb-1">
                                &#x1F4C4;
                            </div>
                            <h2 class="text-sm font-semibold text-sky-900">ASO</h2>
                            <p class="text-xs text-sky-800/80">
                                Atestado de Saúde Ocupacional para colaboradores.
                            </p>
                        </div>
                        @if($asoPermitido)
                            <div class="mt-3 text-xs text-sky-800 flex items-center gap-1 font-medium">
                                <span>Selecionar</span>
                                <span>&rsaquo;</span>
                            </div>
                        @else
                            <p class="mt-3 text-[11px] text-amber-700 font-medium">
                                {{ $temContratoAtivo ? $semPermissaoMsg : $bloqueadoMsg }}
                            </p>
                        @endif
                    </a>

                    {{-- PGR --}}
                    @php $pgrPermitido = $temContratoAtivo && in_array($servicosIds['pgr'] ?? null, $servicosContrato) && ($isMaster || isset($permissionMap['operacional.pgr.create'])); @endphp
                    <a @if($pgrPermitido) href="{{ route('operacional.kanban.pgr.tipo', ['cliente' => $cliente, 'origem'  => $origem]) }}" @endif
                       class="group rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-100 p-4
          flex flex-col justify-between {{ $pgrPermitido ? 'hover:from-emerald-100 hover:to-emerald-200 hover:border-emerald-300 hover:shadow-md' : 'opacity-60 cursor-not-allowed' }}
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center text-white text-xl mb-1">
                                &#x1F4C4;
                            </div>
                            <h2 class="text-sm font-semibold text-emerald-900">PGR</h2>
                            <p class="text-xs text-emerald-800/80">
                                Programa de Gerenciamento de Riscos.
                            </p>
                        </div>
                        @if($pgrPermitido)
                            <div class="mt-3 text-xs text-emerald-800 flex items-center gap-1 font-medium">
                                <span>Selecionar</span>
                                <span>&rsaquo;</span>
                            </div>
                        @else
                            <p class="mt-3 text-[11px] text-amber-700 font-medium">
                                {{ $temContratoAtivo ? $semPermissaoMsg : $bloqueadoMsg }}
                            </p>
                        @endif
                    </a>

                    {{-- PCMSO --}}
                    @php $pcmsoPermitido = $temContratoAtivo && in_array($servicosIds['pcmso'] ?? null, $servicosContrato) && ($isMaster || isset($permissionMap['operacional.pcmso.create'])); @endphp
                    <a @if($pcmsoPermitido) href="{{ route('operacional.pcmso.tipo', ['cliente' => $cliente, 'origem'  => $origem]) }}" @endif
                       class="group rounded-2xl border border-purple-200 bg-gradient-to-br from-purple-50 to-purple-100 p-4
          flex flex-col justify-between {{ $pcmsoPermitido ? 'hover:from-purple-100 hover:to-purple-200 hover:border-purple-300 hover:shadow-md' : 'opacity-60 cursor-not-allowed' }}
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-purple-500 flex items-center justify-center text-white text-xl mb-1">
                                &#x1FA7A;
                            </div>
                            <h2 class="text-sm font-semibold text-purple-900">PCMSO</h2>
                            <p class="text-xs text-purple-800/80">
                                Programa de Controle Médico Ocupacional.
                            </p>
                        </div>
                        @if($pcmsoPermitido)
                            <p class="mt-3 text-[11px] text-purple-700 font-medium">
                                Clique para solicitar o PCMSO.
                            </p>
                        @else
                            <p class="mt-3 text-[11px] text-amber-700 font-medium">
                                {{ $temContratoAtivo ? $semPermissaoMsg : $bloqueadoMsg }}
                            </p>
                        @endif
                    </a>

                    {{-- LTCAT --}}
                    @php $ltcatPermitido = $temContratoAtivo && in_array($servicosIds['ltcat'] ?? null, $servicosContrato) && ($isMaster || isset($permissionMap['operacional.ltcat.create'])); @endphp
                    <a @if($ltcatPermitido) href="{{ route('operacional.ltcat.tipo', ['cliente' => $cliente, 'origem'  => $origem]) }}" @endif
                       class="group rounded-2xl border border-orange-200 bg-gradient-to-br from-orange-50 to-orange-100 p-4
          flex flex-col justify-between {{ $ltcatPermitido ? 'hover:from-orange-100 hover:to-orange-200 hover:border-orange-300 hover:shadow-md' : 'opacity-60 cursor-not-allowed' }}
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-orange-500 flex items-center justify-center text-white text-xl mb-1">
                                &#x1F4D1;
                            </div>
                            <h2 class="text-sm font-semibold text-orange-900">LTCAT</h2>
                            <p class="text-xs text-orange-800/80">
                                Laudo Técnico das Condições Ambientais.
                            </p>
                        </div>

                        @if($ltcatPermitido)
                            <p class="mt-3 text-[11px] text-orange-700 font-semibold">
                                Clique para solicitar o LTCAT.
                            </p>
                        @else
                            <p class="mt-3 text-[11px] text-amber-700 font-medium">
                                {{ $temContratoAtivo ? $semPermissaoMsg : $bloqueadoMsg }}
                            </p>
                        @endif
                    </a>

                    {{-- LTIP --}}
                    @php $ltipPermitido = $temContratoAtivo && in_array($servicosIds['ltip'] ?? null, $servicosContrato) && ($isMaster || isset($permissionMap['operacional.ltip.create'])); @endphp
                    <a @if($ltipPermitido) href="{{ route('operacional.ltip.create', ['cliente' => $cliente, 'origem'  => $origem]) }}" @endif
                       class="group rounded-2xl border border-red-200 bg-gradient-to-br from-red-50 to-red-100 p-4
          flex flex-col justify-between {{ $ltipPermitido ? 'hover:from-red-100 hover:to-red-200 hover:border-red-300 hover:shadow-md' : 'opacity-60 cursor-not-allowed' }}
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-red-600 flex items-center justify-center text-white text-xl mb-1">
                                &#9888;&#65039;
                            </div>
                            <h2 class="text-sm font-semibold text-red-900">LTIP</h2>
                            <p class="text-xs text-red-800/80">
                                Laudo de Insalubridade e Periculosidade.
                            </p>
                        </div>
                        @if($ltipPermitido)
                            <p class="mt-3 text-[11px] text-red-700 font-semibold">
                                Clique para solicitar o LTIP.
                            </p>
                        @else
                            <p class="mt-3 text-[11px] text-amber-700 font-medium">
                                {{ $temContratoAtivo ? $semPermissaoMsg : $bloqueadoMsg }}
                            </p>
                        @endif
                    </a>

                    {{-- APR --}}
                    @php $aprPermitido = $temContratoAtivo && in_array($servicosIds['apr'] ?? null, $servicosContrato) && ($isMaster || isset($permissionMap['operacional.apr.create'])); @endphp
                    <a @if($aprPermitido) href="{{ route('operacional.apr.create', ['cliente' => $cliente, 'origem'  => $origem]) }}" @endif
                       class="group rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-amber-100 p-4
          flex flex-col justify-between {{ $aprPermitido ? 'hover:from-amber-100 hover:to-amber-200 hover:border-amber-300 hover:shadow-md' : 'opacity-60 cursor-not-allowed' }}
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-amber-600 flex items-center justify-center text-white text-xl mb-1">
                                &#9888;&#65039;
                            </div>
                            <h2 class="text-sm font-semibold text-amber-900">APR</h2>
                            <p class="text-xs text-amber-800/80">
                                Análise Preliminar de Riscos da atividade.
                            </p>
                        </div>
                        @if($aprPermitido)
                            <div class="mt-3 text-xs text-amber-800 flex items-center gap-1 font-medium">
                                <span>Selecionar</span>
                                <span>&rsaquo;</span>
                            </div>
                        @else
                            <p class="mt-3 text-[11px] text-amber-700 font-medium">
                                {{ $temContratoAtivo ? $semPermissaoMsg : $bloqueadoMsg }}
                            </p>
                        @endif
                    </a>

                    {{-- PAE --}}
                    @php $paePermitido = $temContratoAtivo && in_array($servicosIds['pae'] ?? null, $servicosContrato) && ($isMaster || isset($permissionMap['operacional.pae.create'])); @endphp
                    <a @if($paePermitido) href="{{ route('operacional.pae.create', ['cliente' => $cliente, 'origem'  => $origem]) }}" @endif
                       class="group rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 to-rose-100 p-4
          flex flex-col justify-between {{ $paePermitido ? 'hover:from-rose-100 hover:to-rose-200 hover:border-rose-300 hover:shadow-md' : 'opacity-60 cursor-not-allowed' }}
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-rose-600 flex items-center justify-center text-white text-xl mb-1">
                                &#x1F6A8;
                            </div>
                            <h2 class="text-sm font-semibold text-rose-900">PAE</h2>
                            <p class="text-xs text-rose-800/80">
                                Plano de Atendimento a Emergências.
                            </p>
                        </div>
                        @if($paePermitido)
                            <p class="mt-3 text-[11px] text-rose-700">
                                Clique para solicitar PAE para este cliente.
                            </p>
                        @else
                            <p class="mt-3 text-[11px] text-amber-700 font-medium">
                                {{ $temContratoAtivo ? $semPermissaoMsg : $bloqueadoMsg }}
                            </p>
                        @endif
                    </a>

                    {{-- Treinamentos NRs --}}
                    @php $treinPermitido = $temContratoAtivo && in_array($servicosIds['treinamentos'] ?? null, $servicosContrato) && ($isMaster || isset($permissionMap['operacional.treinamentos.create'])); @endphp
                    <a @if($treinPermitido) href="{{ route('operacional.treinamentos-nr.create', ['cliente' => $cliente, 'origem'  => $origem]) }}" @endif
                       class="group rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-indigo-100 p-4
          flex flex-col justify-between {{ $treinPermitido ? 'hover:from-indigo-100 hover:to-indigo-200 hover:border-indigo-300 hover:shadow-md' : 'opacity-60 cursor-not-allowed' }}
          transition">
                        <div class="space-y-2">
                            <div class="w-9 h-9 rounded-xl bg-indigo-500 flex items-center justify-center text-white text-xl mb-1">
                                &#x1F393;
                            </div>
                            <h2 class="text-sm font-semibold text-indigo-900">Treinamentos NRs</h2>
                            <p class="text-xs text-indigo-800/80">
                                Normas regulamentadoras e capacitações.
                            </p>
                        </div>
                        @if($treinPermitido)
                            <p class="mt-3 text-[11px] text-indigo-700">
                                Clique para solicitar Treinamento de NRs para este cliente.
                            </p>
                        @else
                            <p class="mt-3 text-[11px] text-amber-700 font-medium">
                                {{ $temContratoAtivo ? $semPermissaoMsg : $bloqueadoMsg }}
                            </p>
                        @endif
                    </a>

                </div>
            </div>
        </div>
    </div>
@endsection

