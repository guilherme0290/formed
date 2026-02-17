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
    @php
        $permPrefix = str_starts_with($routePrefix, 'comercial.') ? 'comercial.clientes' : 'master.clientes';
        $permissionMap = $user?->papel?->permissoes?->pluck('chave')->flip()->all() ?? [];
        $isMaster = $user?->hasPapel('Master');
        $canUpdate = $isMaster || isset($permissionMap[$permPrefix.'.update']);
    @endphp
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
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700">
                {{ session('error') }}
            </div>
        @endif

        @if (session('acesso_cliente'))
            @php($acesso = session('acesso_cliente'))
            <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                <div class="font-semibold">Acesso do cliente criado</div>
                <p>Login: <span class="font-mono">{{ $acesso['email'] }}</span></p>
                <p>Senha temporária: <span class="font-mono">{{ $acesso['senha'] }}</span></p>
                <p class="text-xs text-amber-700 mt-1">O usuário deverá trocar a senha no primeiro login.</p>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow border p-6 space-y-4">
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
            <div class="pt-4 grid sm:grid-cols-2 gap-2">
                <a href="{{ $canUpdate ? route($routePrefix.'.edit',$cliente) : 'javascript:void(0)' }}"
                   @if(!$canUpdate) title="Usuario sem permissao" aria-disabled="true" @endif
                   class="px-4 py-2 rounded-xl text-sm font-semibold text-center w-full {{ $canUpdate ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-slate-200 text-slate-500 cursor-not-allowed' }}">
                    Editar
                </a>
                <a href="{{ route($routePrefix.'.index') }}"
                   class="px-4 py-2 rounded-xl bg-white text-slate-700 border border-slate-200 text-sm font-semibold text-center hover:bg-slate-50 w-full">
                    Voltar
                </a>
            </div>
        </div>

    </div>
@endsection


