@extends('layouts.app')

@section('content')
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

        <div class="flex justify-end mt-6 gap-2">
            <a href="{{ route('clientes.edit',$cliente) }}"
               class="bg-yellow-500 text-white px-4 py-2 rounded-lg">
                Editar
            </a>
            <a href="{{ route('clientes.index') }}"
               class="bg-gray-500 text-white px-4 py-2 rounded-lg">
                Voltar
            </a>
        </div>
    </div>
@endsection
