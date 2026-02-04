@extends('layouts.comercial')
@section('title', 'Itens da Tabela de Preços')
@section('page-container', 'w-full p-0')

@section('content')
    @php($routePrefix = 'comercial')
    @include('comercial.tabela-precos.itens._conteudo', compact('itens','servicos','routePrefix','dashboardRoute'))
@endsection
