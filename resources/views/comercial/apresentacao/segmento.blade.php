@extends('layouts.comercial')
@section('title', 'Escolher Segmento')

@section('content')
    @php
        $cards = [
            'construcao-civil' => [
                'nome' => 'Construção Civil',
                'bg' => 'bg-amber-50',
                'iconBg' => 'bg-amber-500',
                'icon' => 'building',
            ],
            'industria' => [
                'nome' => 'Indústria',
                'bg' => 'bg-sky-50',
                'iconBg' => 'bg-sky-500',
                'icon' => 'factory',
            ],
            'comercio' => [
                'nome' => 'Comércio / Varejo',
                'bg' => 'bg-emerald-50',
                'iconBg' => 'bg-emerald-500',
                'icon' => 'bag',
            ],
            'restaurante' => [
                'nome' => 'Restaurante / Alimentação',
                'bg' => 'bg-rose-50',
                'iconBg' => 'bg-rose-500',
                'icon' => 'utensils',
            ],
        ];
    @endphp

    <div class="min-h-[calc(100vh-64px)] bg-slate-50">
        {{-- Top bar --}}
        <div class="bg-slate-900">
            <div class="max-w-7xl mx-auto px-4 md:px-6 py-4">
                <div class="text-white">
                    <div class="text-lg font-semibold leading-tight">FORMED</div>
                    <div class="text-xs text-slate-200">Medicina e Segurança do Trabalho</div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 md:px-6 py-8">
            <div class="mb-4">
                <a href="{{ route('comercial.apresentacao.cliente') }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-white border border-slate-200 text-slate-700 px-3 py-2 text-sm hover:bg-slate-50">
                    &larr; Voltar
                </a>
            </div>
            <div class="max-w-3xl mx-auto bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden">

                <div class="bg-blue-600 px-6 py-4">
                    <h1 class="text-white font-semibold text-base">Escolha o Segmento</h1>
                </div>

                <div class="p-7 space-y-6">
                    <div class="rounded-2xl bg-blue-50/80 border border-blue-100 px-5 py-4 text-sm text-slate-700 shadow-sm">
                        <div class="text-xs text-slate-500">Cliente: <span class="font-semibold text-slate-800">{{ $cliente['razao_social'] ?? '—' }}</span></div>
                        <div class="text-xs text-slate-500 mt-1">CNPJ: <span class="font-semibold text-slate-800">{{ $cliente['cnpj'] ?? '—' }}</span></div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        @foreach($cards as $key => $card)
                            <a href="{{ route('comercial.apresentacao.show', ['segmento' => $key]) }}"
                               class="group {{ $card['bg'] }} border border-slate-200 rounded-3xl shadow hover:shadow-lg transition p-5 flex items-center gap-4">
                                <div class="h-14 w-14 rounded-2xl {{ $card['iconBg'] }} flex items-center justify-center text-white shadow-sm">
                                    @switch($card['icon'])
                                        @case('building')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M6 21V7a2 2 0 012-2h3v16m3 0V9a2 2 0 012-2h3v14" />
                                            </svg>
                                            @break
                                        @case('factory')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21V10l6 3V10l6 3V10l6 3v8H3z" />
                                            </svg>
                                            @break
                                        @case('bag')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12l-1 14H7L6 7zm3 0a3 3 0 016 0" />
                                            </svg>
                                            @break
                                        @case('utensils')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3v7a2 2 0 01-2 2H4V3m5 0v7a2 2 0 002 2h0V3m6 0v10a2 2 0 01-2 2h0V3m2 0h2a2 2 0 012 2v5a2 2 0 01-2 2h-2" />
                                            </svg>
                                            @break
                                    @endswitch
                                </div>

                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-900 text-base">{{ $card['nome'] }}</div>
                                    <div class="text-xs text-slate-600 mt-0.5">Selecionar →</div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    
            </div>
        </div>
    </div>
@endsection
