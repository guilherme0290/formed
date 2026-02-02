@extends('layouts.master')
@section('title', 'Agendamentos e Tarefas')

@section('content')
    <div class="w-full px-4 md:px-8 py-8 space-y-8">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Agendamentos e Tarefas</h1>
                <p class="text-sm text-slate-500">Resumo por período e serviço</p>
            </div>
            <a href="{{ route('master.dashboard') }}"
               class="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50">
                Voltar ao painel
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6 space-y-5">            <form method="GET" class="grid gap-3 md:grid-cols-7 items-end">
                <div>
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Data início</label>
                    <input type="date" name="data_inicio"
                           value="{{ $agendamentos['data_inicio'] ?? '' }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Data fim</label>
                    <input type="date" name="data_fim"
                           value="{{ $agendamentos['data_fim'] ?? '' }}"
                           class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Serviços</label>
                    <select name="servico"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                        <option value="todos" @selected(($agendamentos['servico_selecionado'] ?? 'todos') === 'todos')>
                            Todos os serviços
                        </option>
                        @foreach(($agendamentos['servicos_disponiveis'] ?? []) as $servico)
                            <option value="{{ $servico->id }}"
                                @selected(($agendamentos['servico_selecionado'] ?? 'todos') == $servico->id)>
                                {{ $servico->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold text-slate-900 whitespace-nowrap">Responsável</label>
                    <select name="responsavel"
                            class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2 h-[44px]">
                        <option value="todos" @selected(($agendamentos['responsavel_selecionado'] ?? 'todos') === 'todos')>
                            Todos os responsáveis
                        </option>
                        @foreach(($agendamentos['responsaveis_disponiveis'] ?? []) as $responsavel)
                            <option value="{{ $responsavel->id }}"
                                @selected(($agendamentos['responsavel_selecionado'] ?? 'todos') == $responsavel->id)>
                                {{ $responsavel->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-1 flex justify-end self-end">
                    <button type="submit"
                            class="w-full md:w-auto h-[44px] px-5 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                        Atualizar
                    </button>
                </div>
                <div class="md:col-span-4 md:col-start-3 text-[11px] text-slate-400 -mt-1">
                    Selecione um serviço específico ou mantenha em todos.
                </div>
            </form>

                                    <div class="grid gap-4 md:grid-cols-7">
                <div class="rounded-2xl border border-amber-200 bg-amber-50/60 px-4 py-4">
                    <div class="text-xs font-bold text-slate-900 uppercase">Pendentes</div>
                    <div class="text-2xl font-semibold text-slate-900 mt-1">
                        {{ $agendamentos['pendentes'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 px-4 py-4">
                    <div class="text-xs font-bold text-slate-900 uppercase">Finalizadas</div>
                    <div class="text-2xl font-semibold text-slate-900 mt-1">
                        {{ $agendamentos['finalizadas'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-2xl border border-sky-200 bg-sky-50/60 px-4 py-4">
                    <div class="text-xs font-bold text-slate-900 uppercase">Em execução</div>
                    <div class="text-2xl font-semibold text-slate-900 mt-1">
                        {{ $agendamentos['em_execucao'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-2xl border border-purple-200 bg-purple-50/60 px-4 py-4">
                    <div class="text-xs font-bold text-slate-900 uppercase">Aguardando fornecedor</div>
                    <div class="text-2xl font-semibold text-slate-900 mt-1">
                        {{ $agendamentos['aguardando_fornecedor'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-2xl border border-orange-200 bg-orange-50/60 px-4 py-4">
                    <div class="text-xs font-bold text-slate-900 uppercase">Correção</div>
                    <div class="text-2xl font-semibold text-slate-900 mt-1">
                        {{ $agendamentos['correcao'] ?? 0 }}
                    </div>
                </div>
                <div class="rounded-2xl border border-rose-200 bg-rose-50/60 px-4 py-4">
                    <div class="text-xs font-bold text-slate-900 uppercase">Atrasados</div>
                    <div class="text-2xl font-semibold text-slate-900 mt-1">
                        {{ $agendamentos['atrasados'] ?? 0 }}
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                <div class="text-xs font-bold text-slate-900 uppercase mb-3">Totais por serviço</div>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse(($agendamentos['por_servico'] ?? []) as $row)
                        <div class="rounded-2xl border border-indigo-200 bg-indigo-50/60 px-4 py-4 flex items-center justify-between">
                            <div class="text-sm font-semibold text-slate-900">
                                {{ $row->servico_nome }}
                            </div>
                            <div class="text-2xl font-semibold text-slate-900">
                                {{ $row->total }}
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-500">Nenhuma tarefa no período selecionado.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
