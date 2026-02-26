@extends('layouts.cliente')

@php
    $activeTab = $activeTab ?? 'faturas';
    $popupTipo = null;
    $popupMensagem = null;

    if (session('ok')) {
        $popupTipo = 'success';
        $popupMensagem = (string) session('ok');
    } elseif (session('erro')) {
        $popupTipo = 'error';
        $popupMensagem = (string) session('erro');
    }
@endphp

@section('title', 'Portal do Cliente')
@section('page-container', 'w-full p-0')
@section('suppress-inline-alerts', '1')

@section('content')
    <section class="w-full px-3 md:px-5 pt-4 md:pt-5">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex w-full sm:w-auto flex-wrap rounded-xl border border-blue-200 bg-white p-1 shadow-sm">
                <a href="{{ route('cliente.faturas') }}"
                   class="flex-1 sm:flex-none text-center px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activeTab === 'faturas' ? 'bg-blue-700 text-white' : 'text-slate-700 hover:bg-blue-50' }}">
                    Faturas
                </a>
                <a href="{{ route('cliente.agendamentos') }}"
                   class="flex-1 sm:flex-none text-center px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activeTab === 'agendamentos' ? 'bg-blue-700 text-white' : 'text-slate-700 hover:bg-blue-50' }}">
                    Agendamentos
                </a>
            </div>

            <a href="{{ route('cliente.dashboard') }}"
               class="inline-flex w-full sm:w-auto items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium border border-slate-300 bg-white text-slate-700 shadow-sm hover:bg-slate-50">
                &larr; Voltar aos servi&ccedil;os
            </a>
        </div>
    </section>

    @if($activeTab === 'agendamentos')
        @include('clientes.portal.partials.agendamentos-content')
    @else
        @include('clientes.portal.partials.faturas-content')
    @endif
@endsection

@if($popupTipo)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const icon = @json($popupTipo);
                const title = @json($popupTipo === 'success' ? 'Sucesso' : 'Erro');
                const text = @json($popupMensagem);

                function openFeedback() {
                    if (!window.Swal || typeof window.Swal.fire !== 'function') {
                        return false;
                    }

                    window.Swal.fire({
                        icon,
                        title,
                        text,
                        confirmButtonText: 'OK'
                    });
                    return true;
                }

                if (openFeedback()) return;

                let attempts = 0;
                const timer = setInterval(function () {
                    attempts += 1;
                    if (openFeedback() || attempts >= 20) {
                        clearInterval(timer);
                    }
                }, 120);
            });
        </script>
    @endpush
@endif


