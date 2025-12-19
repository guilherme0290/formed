@extends('layouts.master')
@section('title', 'Tabela de Preços')

@section('content')
    @php($routePrefix = 'master')
    @include('comercial.tabela-precos.itens._conteudo', compact('itens','servicos','routePrefix','dashboardRoute'))
@endsection
