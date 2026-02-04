@extends('layouts.comercial')
@section('title', 'Apresentação da Proposta')
@section('page-container', 'w-full p-0')

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
    @endphp

    <div class="min-h-screen bg-slate-50">
        <div class="w-full px-2 sm:px-3 md:px-4 py-2 md:py-3">
            <div class="mb-3 flex items-center justify-start print:hidden">
                <a href="{{ route('comercial.dashboard') }}"
                   class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
                    Painel comercial
                </a>
            </div>
            <div class="w-full bg-white rounded-none shadow-sm border-y border-slate-100 overflow-hidden md:rounded-3xl md:border">

                {{-- Header do documento --}}
                <div class="{{ $theme['headerBg'] }} px-6 py-4 flex items-center justify-between gap-3">
                    <div class="text-white">
                        <div class="text-lg font-semibold leading-tight">FORMED</div>
                        <div class="text-xs text-white/80">Medicina e Segurança do Trabalho</div>
                    </div>

                    <div class="flex items-center gap-3">
                        <img id="clienteLogoPreview"
                             alt="Logo do cliente"
                             class="{{ $clienteLogoData ? '' : 'hidden' }} h-12 w-auto rounded-lg bg-white/90 p-1"
                             src="{{ $clienteLogoData ?? '' }}" />
                        <div class="flex items-center gap-2 print:hidden">
                            <label for="clienteLogoInput"
                                   class="rounded-xl bg-white/95 hover:bg-white border border-white/50 text-slate-800 px-3 py-1.5 text-xs font-semibold cursor-pointer">
                                Logo do cliente
                            </label>
                            <button type="button" id="clienteLogoRemove"
                                    class="rounded-xl bg-white/95 hover:bg-white border border-white/50 text-slate-800 px-3 py-1.5 text-xs font-semibold">
                                Remover logo
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 print:hidden">
                        <a href="{{ route('comercial.apresentacao.pdf', $segmento) }}"
                           target="_blank"
                           rel="noopener"
                           class="rounded-xl bg-white/95 hover:bg-white border border-white/50 text-slate-800 px-3 py-1.5 text-xs font-semibold">
                            Imprimir
                        </a>
                        <a href="{{ route('comercial.apresentacao.modelo', $segmento) }}"
                           class="rounded-xl bg-white/95 hover:bg-white border border-white/50 text-slate-800 px-3 py-1.5 text-xs font-semibold">
                            Configurar modelo
                        </a>
                        <a href="{{ route('comercial.apresentacao.segmento') }}"
                           class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900">
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
                            <div class="md:col-span-2">
                                <label class="text-xs font-semibold text-slate-600">Logo do cliente</label>
                                <input id="clienteLogoInput" type="file" accept="image/*"
                                       class="mt-1 w-full rounded-xl border border-slate-200 text-sm px-3 py-2">
                                <p class="mt-1 text-[11px] text-slate-500">A logo aparece apenas nesta apresentação.</p>
                            </div>
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
                            <span class="text-sm font-extrabold tracking-wide">{{ $tituloSegmento }}</span>
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

                    @if(($precos ?? collect())->count())
                        <div class="rounded-2xl bg-white border border-slate-200 p-6">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
                                </svg>
                                <h3 class="text-sm font-semibold text-slate-900">Serviços</h3>
                            </div>

                            <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-xs text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Item</th>
                                        <th class="px-4 py-3 text-right font-semibold">Qtd</th>
                                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                    @foreach($precos as $preco)
                                        @php
                                            $item = $preco->tabelaPrecoItem;
                                            $descricao = $item?->descricao ?: $item?->servico?->nome ?: 'Item';
                                            $qtd = (float) $preco->quantidade;
                                            $valor = (float) ($item?->preco ?? 0);
                                            $total = $qtd * $valor;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900">{{ $item?->codigo }}</div>

                                                    <div class="text-xs text-slate-500">Código: {{ $descricao }}</div>

                                            </td>
                                            <td class="px-4 py-3 text-right">{{ number_format($qtd, 2, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right">R$ {{ number_format($total, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(($exames ?? collect())->count())
                        <div class="rounded-2xl bg-white border border-slate-200 p-6">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 01-2 2H7a2 2 0 01-2-2m14 0a2 2 0 00-2-2H7a2 2 0 00-2 2"/>
                                </svg>
                                <h3 class="text-sm font-semibold text-slate-900">Exames</h3>
                            </div>

                            <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-xs text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Exame</th>
                                        <th class="px-4 py-3 text-right font-semibold">Qtd</th>
                                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                    @foreach($exames as $exameRow)
                                        @php
                                            $exame = $exameRow->exame ?? null;
                                            $qtd = (float) ($exameRow->quantidade ?? 1);
                                            $valor = (float) ($exame?->preco ?? 0);
                                            $total = $qtd * $valor;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900">{{ $exame?->titulo ?? 'Exame' }}</div>
                                                @if(!empty($exame?->descricao))
                                                    <div class="text-xs text-slate-500">{{ $exame->descricao }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">{{ number_format($qtd, 2, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right">R$ {{ number_format($total, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @php
                        $treinamentosComPreco = ($treinamentos ?? collect())
                            ->filter(fn($treinamentoRow) => !empty($treinamentoRow->tabelaItem)
                                && $treinamentoRow->tabelaItem->preco !== null);
                    @endphp

                    @if($treinamentosComPreco->count())
                        <div class="rounded-2xl bg-white border border-slate-200 p-6">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zM12 14l6.16-3.422M12 14v7m0 0l-9-5m9 5l9-5"/>
                                </svg>
                                <h3 class="text-sm font-semibold text-slate-900">Treinamentos</h3>
                            </div>

                            <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-xs text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Treinamento</th>
                                        <th class="px-4 py-3 text-right font-semibold">Qtd</th>
                                        <th class="px-4 py-3 text-right font-semibold">Total</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                    @foreach($treinamentosComPreco as $treinamentoRow)
                                        @php
                                            $treinamento = $treinamentoRow->treinamento ?? null;
                                            $qtd = (float) ($treinamentoRow->quantidade ?? 1);
                                            $valor = (float) ($treinamentoRow->tabelaItem?->preco ?? 0);
                                            $total = $qtd * $valor;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-slate-900">{{ $treinamento?->codigo ?? 'NR' }} — {{ $treinamento?->titulo ?? 'Treinamento' }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-right">{{ number_format($qtd, 2, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right">R$ {{ number_format($total, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(($esocialFaixas ?? collect())->count())
                        <div class="rounded-2xl bg-white border border-slate-200 p-6">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/>
                                </svg>
                                <h3 class="text-sm font-semibold text-slate-900">eSocial</h3>
                            </div>

                            @if(!empty($esocialDescricao))
                                <div class="mt-3 prose prose-sm max-w-none text-slate-700">
                                    {!! $esocialDescricao !!}
                                </div>
                            @endif

                            <div class="mt-4 overflow-x-auto rounded-2xl border border-slate-200">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-xs text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Faixa</th>
                                        <th class="px-4 py-3 text-left font-semibold">Descrição</th>
                                        <th class="px-4 py-3 text-right font-semibold">Valor</th>
                                    </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                    @foreach($esocialFaixas as $faixa)
                                        <tr>
                                            <td class="px-4 py-3">
                                                {{ $faixa->inicio }}@if($faixa->fim) - {{ $faixa->fim }}@else+@endif
                                            </td>
                                            <td class="px-4 py-3">{{ $faixa->descricao }}</td>
                                            <td class="px-4 py-3 text-right">R$ {{ number_format((float) $faixa->preco, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

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
                            <div class="text-slate-500 text-xs mt-2">{{ $conteudo['rodape'] ?? 'comercial@formed.com.br • (00) 0000-0000' }}</div>
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
                const logoInput = document.getElementById('clienteLogoInput');
                const logoPreview = document.getElementById('clienteLogoPreview');
                const logoUploadUrl = @json(route('comercial.apresentacao.logo'));
                const logoRemoveUrl = @json(route('comercial.apresentacao.logo.destroy'));
                const logoRemoveButton = document.getElementById('clienteLogoRemove');
                const csrfToken = @json(csrf_token());

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

                logoInput?.addEventListener('change', async () => {
                    const file = logoInput.files?.[0];
                    if (!file || !logoPreview) {
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = () => {
                        logoPreview.src = String(reader.result || '');
                        logoPreview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);

                    const formData = new FormData();
                    formData.append('logo', file);

                    try {
                        await fetch(logoUploadUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });
                    } catch (e) {
                        console.error(e);
                    }
                });

                logoRemoveButton?.addEventListener('click', async () => {
                    if (logoPreview) {
                        logoPreview.src = '';
                        logoPreview.classList.add('hidden');
                    }
                    if (logoInput) {
                        logoInput.value = '';
                    }

                    try {
                        await fetch(logoRemoveUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                        });
                    } catch (e) {
                        console.error(e);
                    }
                });
            })();
        </script>
    @endpush
@endsection
