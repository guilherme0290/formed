@extends('layouts.comercial')
@section('title', 'Apresentação da Proposta')

@section('content')
    @php
        $themeBySegment = [
            'construcao-civil' => [
                'headerBg' => 'bg-amber-700',
                'apresentacaoBg' => 'bg-amber-50',
                'apresentacaoBar' => 'bg-amber-600',
                'titulo' => 'CONSTRUÇÃO CIVIL',
            ],
            'industria' => [
                'headerBg' => 'bg-blue-600',
                'apresentacaoBg' => 'bg-blue-50',
                'apresentacaoBar' => 'bg-blue-600',
                'titulo' => 'INDÚSTRIA',
            ],
            'comercio' => [
                'headerBg' => 'bg-emerald-600',
                'apresentacaoBg' => 'bg-emerald-50',
                'apresentacaoBar' => 'bg-emerald-600',
                'titulo' => 'COMÉRCIO / VAREJO / SUPERMERCADOS',
            ],
            'restaurante' => [
                'headerBg' => 'bg-red-600',
                'apresentacaoBg' => 'bg-rose-50',
                'apresentacaoBar' => 'bg-red-600',
                'titulo' => 'RESTAURANTE / ALIMENTAÇÃO',
            ],
        ];

        $theme = $themeBySegment[$segmento] ?? $themeBySegment['construcao-civil'];

        $conteudo = [
            'construcao-civil' => [
                'intro' => [
                    'A FORMED apoia empresas da construção civil com uma apresentação clara e objetiva dos serviços de SST, alinhada às exigências legais e à rotina do canteiro.',
                    'Nosso foco é reduzir riscos, organizar documentação e manter a operação em conformidade de forma simples e contínua.',
                ],
                'servicos' => [
                    'PGR e inventário de riscos',
                    'PCMSO e gestão de exames ocupacionais',
                    'ASO (admissional, periódico e demissional)',
                    'Treinamentos obrigatórios (NRs)',
                    'Gestão de documentos e laudos (LTCAT, APR, PAE)',
                ],
                'beneficios' => 'Organização e previsibilidade no atendimento, redução de riscos e suporte contínuo para manter a obra em conformidade.',
            ],
            'industria' => [
                'intro' => [
                    'Para operações industriais, a FORMED entrega uma solução robusta de SST com foco em controle de riscos, gestão documental e suporte contínuo.',
                    'Prontidão para auditorias, conformidade e rotina organizada para a equipe de segurança e RH.',
                ],
                'servicos' => [
                    'PGR (inventário e plano de ação)',
                    'PCMSO e gestão de ASOs',
                    'Gestão de exames complementares',
                    'Laudos técnicos e documentação (LTCAT)',
                    'Treinamentos e capacitações (NRs)',
                ],
                'beneficios' => 'Menos retrabalho, mais controle, prontidão para auditorias e melhoria contínua de segurança e produtividade.',
            ],
            'comercio' => [
                'intro' => [
                    'A FORMED simplifica a gestão de SST para empresas de comércio e varejo com atendimento objetivo e documentação organizada.',
                    'Apoio recorrente para manter a empresa regular sem travar a operação.',
                ],
                'servicos' => [
                    'PGR e documentação obrigatória',
                    'PCMSO e ASO',
                    'Gestão de exames ocupacionais',
                    'Treinamentos aplicáveis (NRs)',
                    'Suporte para rotinas e fiscalizações',
                ],
                'beneficios' => 'Regularidade com agilidade, redução de riscos e tranquilidade para o gestor focar na operação.',
            ],
            'restaurante' => [
                'intro' => [
                    'A FORMED oferece suporte completo em SST para o segmento de alimentação, com foco em conformidade e prevenção de riscos.',
                    'Documentação em dia e apoio contínuo para manter a operação segura e regular.',
                ],
                'servicos' => [
                    'PGR e documentação de SST',
                    'PCMSO e ASO',
                    'Gestão de exames ocupacionais',
                    'Treinamentos e orientações aplicáveis',
                    'Suporte contínuo para adequações',
                ],
                'beneficios' => 'Atendimento prático, documentação em dia e apoio para manter a operação segura e regular.',
            ],
        ][$segmento] ?? [];
    @endphp

    <div class="min-h-[calc(100vh-64px)] bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 md:px-6 py-6">
            <div class="max-w-4xl mx-auto bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">

                {{-- Header do documento --}}
                <div class="{{ $theme['headerBg'] }} px-6 py-4 flex items-center justify-between gap-3">
                    <div class="text-white">
                        <div class="text-lg font-semibold leading-tight">FORMED</div>
                        <div class="text-xs text-white/80">Medicina e Segurança do Trabalho</div>
                    </div>

                    <div class="flex items-center gap-2 print:hidden">
                        <a href="{{ route('comercial.apresentacao.pdf', $segmento) }}"
                           target="_blank"
                           rel="noopener"
                           class="rounded-xl bg-white/95 hover:bg-white border border-white/50 text-slate-800 px-3 py-1.5 text-xs font-semibold">
                            Imprimir
                        </a>
                        <a href="{{ route('comercial.apresentacao.segmento') }}"
                           class="rounded-xl bg-white/95 hover:bg-white border border-white/50 text-slate-800 px-3 py-1.5 text-xs font-semibold">
                            Voltar
                        </a>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <div class="print:hidden rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">Atualizar dados do cliente</div>
                                <p class="text-xs text-slate-500">Buscar CNPJ e preencher razão social, contato e telefone.</p>
                            </div>
                            <span id="cnpjMsg" class="text-[11px] text-slate-500 hidden"></span>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-semibold text-slate-600">CNPJ</label>
                                <div class="mt-1 flex gap-2">
                                    <input id="cnpj" type="text"
                                           value="{{ $cliente['cnpj'] ?? '' }}"
                                           class="flex-1 rounded-xl border border-slate-200 text-sm px-3 py-2"
                                           placeholder="00.000.000/0000-00">
                                    <button type="button" id="btnBuscarCnpj"
                                            class="rounded-xl bg-slate-900 hover:bg-slate-800 text-white px-4 py-2 text-sm font-semibold">
                                        Buscar
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Razão Social</label>
                                <input id="razao_social" type="text"
                                       value="{{ $cliente['razao_social'] ?? '' }}"
                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Nome do contato</label>
                                <input id="contato" type="text"
                                       value="{{ $cliente['contato'] ?? '' }}"
                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600">Telefone</label>
                                <input id="telefone" type="text"
                                       value="{{ $cliente['telefone'] ?? '' }}"
                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                            </div>
                        </div>
                    </div>

                    {{-- Apresentação para --}}
                    <div class="rounded-2xl {{ $theme['apresentacaoBg'] }} border border-slate-200 overflow-hidden">
                        <div class="grid grid-cols-[6px,1fr]">
                            <div class="{{ $theme['apresentacaoBar'] }}"></div>
                            <div class="p-5">
                                <div class="font-semibold text-slate-900">Apresentação para:</div>
                                <div class="mt-2 space-y-1 text-sm text-slate-700">
                                    <div><span class="text-slate-500">Razão Social:</span> <span id="view_razao_social" class="font-semibold">{{ $cliente['razao_social'] ?? '—' }}</span></div>
                                    <div><span class="text-slate-500">CNPJ:</span> <span id="view_cnpj" class="font-semibold">{{ $cliente['cnpj'] ?? '—' }}</span></div>
                                    <div><span class="text-slate-500">Contato:</span> <span id="view_contato" class="font-semibold">{{ $cliente['contato'] ?? '—' }}</span></div>
                                    <div><span class="text-slate-500">Telefone:</span> <span id="view_telefone" class="font-semibold">{{ $cliente['telefone'] ?? '—' }}</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bloco do segmento (azul-marinho fixo) --}}
                    <div class="rounded-2xl bg-slate-900 text-white p-6 text-center">
                        <div class="inline-flex items-center gap-2 justify-center">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-500">
                                <span class="h-2 w-2 rounded-full bg-white/90"></span>
                            </span>
                            <span class="text-sm font-extrabold tracking-wide">{{ $theme['titulo'] }}</span>
                        </div>

                        <div class="mt-4 space-y-2 text-sm text-white/85 text-left md:text-center">
                            <p>{{ $conteudo['intro'][0] ?? '' }}</p>
                            <p>{{ $conteudo['intro'][1] ?? '' }}</p>
                        </div>
                    </div>

                    {{-- Serviços Essenciais --}}
                    <div class="rounded-2xl bg-white border border-slate-200 p-6">
                        <div class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-6 4h6m-7 4h8m-10 6h12V4H6v16z"/>
                            </svg>
                            <h3 class="text-sm font-semibold text-slate-900">Serviços Essenciais</h3>
                        </div>

                        <ul class="mt-4 space-y-2 text-sm text-slate-700">
                            @foreach(($conteudo['servicos'] ?? []) as $s)
                                <li class="flex items-start gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    <span>{{ $s }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Benefícios --}}
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 overflow-hidden">
                        <div class="grid grid-cols-[6px,1fr]">
                            <div class="bg-emerald-500"></div>
                            <div class="p-6">
                                <div class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    <h3 class="text-sm font-semibold text-emerald-800">Benefícios</h3>
                                </div>
                                <p class="mt-3 text-sm text-emerald-900/80">
                                    {{ $conteudo['beneficios'] ?? '' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Rodapé --}}
                    <div class="pt-4">
                        <div class="h-px bg-blue-600/30"></div>
                        <div class="mt-4 text-center text-sm">
                            <div class="font-semibold text-blue-700">FORMED</div>
                            <div class="text-slate-600 text-xs mt-0.5">Medicina e Segurança do Trabalho</div>
                            <div class="text-slate-500 text-xs mt-2">comercial@formed.com.br • (00) 0000-0000</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const cnpj = document.getElementById('cnpj');
                const razao = document.getElementById('razao_social');
                const contato = document.getElementById('contato');
                const telefone = document.getElementById('telefone');
                const btnBuscar = document.getElementById('btnBuscarCnpj');
                const cnpjMsg = document.getElementById('cnpjMsg');

                const viewRazao = document.getElementById('view_razao_social');
                const viewCnpj = document.getElementById('view_cnpj');
                const viewContato = document.getElementById('view_contato');
                const viewTelefone = document.getElementById('view_telefone');

                function setMsg(type, text) {
                    if (!cnpjMsg) return;
                    cnpjMsg.classList.remove('hidden');
                    cnpjMsg.className = 'text-[11px]';
                    cnpjMsg.classList.add(type === 'err' ? 'text-red-600' : 'text-slate-500');
                    cnpjMsg.textContent = text;
                }

                function clearMsg() {
                    cnpjMsg?.classList.add('hidden');
                }

                function syncPreview() {
                    if (viewRazao && razao) viewRazao.textContent = razao.value || '—';
                    if (viewCnpj && cnpj) viewCnpj.textContent = cnpj.value || '—';
                    if (viewContato && contato) viewContato.textContent = contato.value || '—';
                    if (viewTelefone && telefone) viewTelefone.textContent = telefone.value || '—';
                }

                [cnpj, razao, contato, telefone].forEach((el) => {
                    el?.addEventListener('input', syncPreview);
                });

                btnBuscar?.addEventListener('click', async () => {
                    clearMsg();
                    const raw = (cnpj?.value || '').trim();
                    const digits = raw.replace(/\D+/g, '');
                    if (!digits) return setMsg('err', 'Informe um CNPJ.');

                    btnBuscar.disabled = true;
                    btnBuscar.textContent = 'Buscando...';

                    try {
                        const url = @json(route('comercial.clientes.consulta-cnpj', ['cnpj' => '__CNPJ__']))
                            .replace('__CNPJ__', encodeURIComponent(digits));
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
                        const json = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            return setMsg('err', json?.error || 'Falha ao consultar CNPJ.');
                        }

                        if (razao && json?.razao_social) razao.value = json.razao_social;
                        if (contato && (json?.contato || json?.nome_fantasia)) {
                            contato.value = json?.contato || json?.nome_fantasia;
                        }
                        if (telefone && (json?.telefone || json?.telefone1 || json?.telefone2)) {
                            telefone.value = json?.telefone || json?.telefone1 || json?.telefone2;
                        }
                        syncPreview();
                        setMsg('ok', 'Dados preenchidos com sucesso.');
                    } catch (e) {
                        console.error(e);
                        setMsg('err', 'Falha ao consultar CNPJ.');
                    } finally {
                        btnBuscar.disabled = false;
                        btnBuscar.textContent = 'Buscar';
                    }
                });
            })();
        </script>
    @endpush
@endsection
