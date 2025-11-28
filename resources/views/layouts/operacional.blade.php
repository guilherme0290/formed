<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Painel Operacional') - Formed</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900">
<div class="min-h-screen flex">

    {{-- Sidebar esquerda --}}
    <aside class="hidden md:flex flex-col w-56 bg-slate-950 text-slate-100">
        <div class="h-16 flex items-center px-6 text-lg font-semibold">
            Operacional
        </div>

        <nav class="flex-1 px-3 mt-4 space-y-1">
            <a href="{{ route('operacional.kanban') }}"
               class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800 text-slate-50 text-sm font-medium">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-700">
                    🗂️
                </span>
                <span>Painel Operacional</span>
            </a>
        </nav>

        <div class="px-4 py-4 border-t border-slate-800 space-y-2 text-sm">
            <a href="{{ url('/') }}" class="flex items-center gap-2 text-slate-300 hover:text-white">
                <span>⏪</span> <span>Voltar ao Início</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="flex items-center gap-2 text-rose-400 hover:text-rose-300">
                    <span>🚪</span> Sair
                </button>
            </form>
        </div>
    </aside>

    {{-- Área principal à direita --}}
    <div class="flex-1 flex flex-col bg-slate-50">

        <header class="bg-blue-900 text-white shadow-sm">
            <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between">
                <div class="flex items-baseline gap-3">
                    <span class="font-semibold text-lg tracking-tight">FORMED</span>
                    <span class="text-xs md:text-sm text-blue-100">
                    Medicina e Segurança do Trabalho
                </span>
                </div>

                <div class="flex items-center gap-3 text-xs md:text-sm text-blue-50">
                <span class="hidden md:inline">
                    {{ auth()->user()->name ?? '' }}
                </span>
                </div>
            </div>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>
    </div>
</div>

{{-- SortableJS para drag & drop do Kanban --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

{{-- Scripts específicos das views --}}
@stack('scripts')

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            function abrirModal(modalId, targetSelectId) {
                const modal = document.getElementById(modalId);
                if (!modal) return;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.dataset.funcaoTarget = targetSelectId;

                const input = modal.querySelector('[data-funcao-input]');
                const erro  = modal.querySelector('[data-funcao-error]');

                if (erro) {
                    erro.textContent = '';
                    erro.classList.add('hidden');
                }
                if (input) {
                    input.value = '';
                    input.focus();
                }
            }

            function fecharModal(modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                modal.dataset.funcaoTarget = '';
            }

            // Clique no botão "+"
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('[data-funcao-open-modal]');
                if (btn) {
                    const modalId   = btn.getAttribute('data-funcao-open-modal');
                    const targetSel = btn.getAttribute('data-funcao-target');
                    abrirModal(modalId, targetSel);
                }
            });

            // Eventos internos do modal (cancelar / salvar)
            document.addEventListener('click', function (e) {
                const modal = e.target.closest('[data-funcao-modal]');
                if (!modal) return;

                // Cancelar
                if (e.target.matches('[data-funcao-cancel]')) {
                    fecharModal(modal);
                    return;
                }

                // Salvar
                if (e.target.matches('[data-funcao-save]')) {
                    const input  = modal.querySelector('[data-funcao-input]');
                    const erroEl = modal.querySelector('[data-funcao-error]');
                    const nome   = (input?.value || '').trim();

                    if (!nome) {
                        if (erroEl) {
                            erroEl.textContent = 'Informe o nome da função.';
                            erroEl.classList.remove('hidden');
                        }
                        if (input) input.focus();
                        return;
                    }

                    const route = modal.dataset.funcaoRoute;
                    const token = modal.dataset.funcaoCsrf;
                    const targetId = modal.dataset.funcaoTarget;

                    if (!route || !token || !targetId) return;

                    // desabilita botão
                    const btnSave = e.target;
                    btnSave.disabled = true;

                    fetch(route, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ nome: nome })
                    })
                        .then(r => r.json())
                        .then(json => {
                            if (!json.ok) {
                                if (erroEl) {
                                    erroEl.textContent = json.message || 'Não foi possível salvar a função.';
                                    erroEl.classList.remove('hidden');
                                }
                                return;
                            }

                            const select = document.getElementById(targetId);
                            if (select) {
                                const opt = document.createElement('option');
                                opt.value = json.id;
                                opt.textContent = json.nome;
                                select.appendChild(opt);
                                select.value = json.id;
                            }

                            fecharModal(modal);
                        })
                        .catch(() => {
                            if (erroEl) {
                                erroEl.textContent = 'Erro na comunicação com o servidor.';
                                erroEl.classList.remove('hidden');
                            }
                        })
                        .finally(() => {
                            btnSave.disabled = false;
                        });
                }
            });

            // Fechar clicando fora do conteúdo (opcional)
            document.addEventListener('click', function (e) {
                const modal = e.target.closest('[data-funcao-modal]');
                if (!modal) return;

                // se clicou diretamente no fundo escuro
                if (e.target === modal) {
                    fecharModal(modal);
                }
            });
        });
    </script>
@endpush

</body>
</html>
