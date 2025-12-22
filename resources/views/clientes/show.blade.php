@php
    $user = auth()->user();
    $layout = 'layouts.app';

    if ($user && optional($user->papel)->nome === 'Operacional') {
        $layout = 'layouts.operacional';
    } else if ($user && optional($user->papel)->nome === 'Master') {
        $layout = 'layouts.master';
    } else if ($user && optional($user->papel)->nome === 'Comercial') {
        $layout = 'layouts.comercial';
    }
@endphp

@extends($layout)

@section('content')
    @php($routePrefix = $routePrefix ?? 'clientes')
    <div class="max-w-3xl mx-auto px-4 py-6">

        @if (session('ok'))
            <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
                {{ session('ok') }}
            </div>
        @endif

        @if (session('erro'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                {{ session('erro') }}
            </div>
        @endif

        @if (session('acesso_cliente'))
            @php $acesso = session('acesso_cliente'); @endphp
            <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                <div class="font-semibold">Acesso do cliente criado</div>
                <p>Login: <span class="font-mono">{{ $acesso['email'] }}</span></p>
                <p>Senha temporária: <span class="font-mono">{{ $acesso['senha'] }}</span></p>
                <p class="text-xs text-amber-700 mt-1">O usuário deverá trocar a senha no primeiro login.</p>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow border p-6 space-y-2">
            <h1 class="text-xl font-semibold mb-2">{{ $cliente->razao_social }}</h1>
            @if($cliente->nome_fantasia)
                <p class="text-sm text-gray-500 mb-2">{{ $cliente->nome_fantasia }}</p>
            @endif

            <p><strong>CNPJ:</strong> {{ $cliente->cnpj }}</p>
            <p><strong>E-mail:</strong> {{ $cliente->email ?? '-' }}</p>
            <p><strong>Telefone:</strong> {{ $cliente->telefone ?? '-' }}</p>
            <p><strong>CEP:</strong> {{ $cliente->cep ?? '-' }}</p>
            <p><strong>Bairro:</strong> {{ $cliente->bairro ?? '-' }}</p>
            <p><strong>Endereço:</strong> {{ $cliente->endereco ?? '-' }}</p>
            <p><strong>Ativo:</strong> {{ $cliente->ativo ? 'Sim' : 'Não' }}</p>
        </div>

        <div class="mt-6 grid md:grid-cols-2 gap-3">
            <div class="bg-white rounded-xl shadow border p-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Acesso do Cliente</p>
                        <p class="text-xs text-slate-500">Use o e-mail cadastrado como login</p>
                    </div>
                </div>
                <div class="text-sm text-slate-600">
                    <p><strong>E-mail sugerido:</strong> {{ $cliente->email ?? '—' }}</p>
                    <p class="text-xs text-slate-500">Uma senha temporária será gerada e o usuário deve trocar no primeiro login.</p>
                </div>
                <form method="POST" action="{{ route($routePrefix.'.acesso', $cliente) }}" onsubmit="return confirm('Criar acesso para este cliente?')">
                    @csrf
                    <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 {{ $cliente->email ? '' : 'opacity-50 cursor-not-allowed' }}" {{ $cliente->email ? '' : 'disabled' }}>
                        🔑 Criar usuário do cliente
                    </button>
                </form>
            </div>

            <div class="flex flex-col md:flex-row justify-end md:items-start gap-2">
                <a href="{{ route($routePrefix.'.edit',$cliente) }}"
                   class="bg-yellow-500 text-white px-4 py-2 rounded-lg text-center">
                    Editar
                </a>
                <a href="{{ route($routePrefix.'.index') }}"
                   class="bg-gray-500 text-white px-4 py-2 rounded-lg text-center">
                    Voltar
                </a>
            </div>
        </div>

    </div>
@endsection
