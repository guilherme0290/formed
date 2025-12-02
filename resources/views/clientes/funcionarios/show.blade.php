{{-- resources/views/cliente/funcionarios/show.blade.php --}}
@extends('layouts.cliente')

@section('title', 'Funcionário')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                {{ $funcionario->nome }}
            </h1>
            <p class="text-xs text-slate-500">
                CPF: {{ $funcionario->cpf }} · Cliente: {{ $cliente->nome_fantasia ?? $cliente->razao_social }}
            </p>
        </div>

        <a href="{{ route('cliente.funcionarios.index') }}"
           class="text-xs text-[color:var(--color-brand-azul)] hover:underline">
            Voltar para lista
        </a>
    </div>

    {{-- Abas --}}
    <div class="border-b border-slate-200 mb-4">
        <nav class="flex gap-4 text-xs">
            <a href="{{ route('cliente.funcionarios.show', ['funcionario' => $funcionario->id, 'tab' => 'geral']) }}"
               class="pb-2 {{ $tab === 'geral' ? 'border-b-2 border-[color:var(--color-brand-azul)] text-slate-900' : 'text-slate-500' }}">
                Cadastro geral
            </a>

            <a href="{{ route('cliente.funcionarios.show', ['funcionario' => $funcionario->id, 'tab' => 'documentos']) }}"
               class="pb-2 {{ $tab === 'documentos' ? 'border-b-2 border-[color:var(--color-brand-azul)] text-slate-900' : 'text-slate-500' }}">
                Documentos
            </a>
        </nav>
    </div>

    @if($tab === 'geral')
        <div class="bg-white rounded-2xl border border-slate-200 p-4 text-sm space-y-2">
            <div>
                <span class="text-xs text-slate-500">Função</span>
                <p class="text-sm text-slate-800">{{ $funcionario->funcao ?: '—' }}</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <span class="text-xs text-slate-500">Data de Nascimento</span>
                    <p class="text-sm text-slate-800">
                        {{ optional($funcionario->data_nascimento)->format('d/m/Y') ?: '—' }}
                    </p>
                </div>
                <div>
                    <span class="text-xs text-slate-500">Data de Admissão</span>
                    <p class="text-sm text-slate-800">
                        {{ optional($funcionario->data_admissao)->format('d/m/Y') ?: '—' }}
                    </p>
                </div>
            </div>

            <div class="pt-2 border-t border-slate-100 mt-2 grid gap-2 md:grid-cols-3 text-xs">
                <p>Treinamento NR: <strong>{{ $funcionario->treinamento_nr ? 'Sim' : 'Não' }}</strong></p>
                <p>Exame Admissional: <strong>{{ $funcionario->exame_admissional ? 'Sim' : 'Não' }}</strong></p>
                <p>Exame Periódico: <strong>{{ $funcionario->exame_periodico ? 'Sim' : 'Não' }}</strong></p>
                <p>Exame Demissional: <strong>{{ $funcionario->exame_demissional ? 'Sim' : 'Não' }}</strong></p>
                <p>Mudança de Função: <strong>{{ $funcionario->exame_mudanca_funcao ? 'Sim' : 'Não' }}</strong></p>
                <p>Retorno ao Trabalho: <strong>{{ $funcionario->exame_retorno_trabalho ? 'Sim' : 'Não' }}</strong></p>
            </div>
        </div>
    @elseif($tab === 'documentos')
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 mb-3">
                Documentos vinculados ao funcionário. No portal do cliente é permitido apenas visualizar e baixar.
            </p>

            @if($documentos->isEmpty())
                <p class="text-xs text-slate-500">
                    Nenhum documento disponível para este funcionário.
                </p>
            @else
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left">Tipo</th>
                        <th class="px-3 py-2 text-left">Título</th>
                        <th class="px-3 py-2 text-left">Válido até</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($documentos as $doc)
                        <tr>
                            <td class="px-3 py-2">{{ strtoupper($doc->tipo) }}</td>
                            <td class="px-3 py-2">{{ $doc->titulo ?? '—' }}</td>
                            <td class="px-3 py-2">
                                {{ optional($doc->valido_ate)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ Storage::disk('public')->url($doc->arquivo_path) }}"
                                   target="_blank"
                                   class="text-[color:var(--color-brand-azul)] hover:underline">
                                    Baixar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif
@endsection
