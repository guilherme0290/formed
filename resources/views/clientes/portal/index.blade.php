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
        <div class="mb-3 inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
            <a href="{{ route('cliente.faturas') }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activeTab === 'faturas' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                Faturas
            </a>
            <a href="{{ route('cliente.agendamentos') }}"
               class="px-4 py-2 rounded-lg text-sm font-semibold transition {{ $activeTab === 'agendamentos' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                Agendamentos
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
