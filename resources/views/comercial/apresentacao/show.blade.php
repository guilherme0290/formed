@extends('layouts.comercial')
@section('title', 'Apresentação da Proposta')
@section('page-container', 'w-full p-0')

@section('content')
    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(251,191,36,0.12),_transparent_28%),linear-gradient(180deg,_#f8fafc_0%,_#eef2f7_100%)]">
        <div class="w-full px-3 py-3 sm:px-4 md:px-5">
            <div class="mb-4 rounded-[30px] border border-slate-200/80 bg-white/90 p-5 shadow-sm backdrop-blur print:hidden">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-4">
                        <a href="{{ route('comercial.dashboard') }}"
                           class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-white hover:text-slate-900">
                            Painel comercial
                        </a>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-amber-700">
                                    Apresentacao comercial
                                </span>
                                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-600">
                                    {{ $segmentoNome }}
                                </span>
                            </div>
                            <h1 class="mt-3 text-2xl font-semibold tracking-[-0.04em] text-slate-950 sm:text-3xl">
                                {{ $cliente['razao_social'] ?? 'Cliente sem nome' }}
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm text-slate-600">
                                Edite apenas os dados essenciais na lateral e use a área principal para validar a apresentação final antes de gerar o PDF.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('comercial.apresentacao.modelo', $segmento) }}"
                           class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Configurar modelo
                        </a>
                        <a href="{{ route('comercial.apresentacao.pdf', $segmento) }}"
                           target="_blank"
                           rel="noopener"
                           class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                            Gerar PDF
                        </a>
                    </div>
                </div>

            </div>

            <div class="grid gap-4 xl:grid-cols-[340px,minmax(0,1fr)]">
                <aside class="print:hidden">
                    <div class="sticky top-4 space-y-4">
                        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">Editar dados</div>
                                    <p class="mt-1 text-xs text-slate-500">Ajustes rápidos sem sair do preview.</p>
                                </div>
                                <span id="cnpjMsg" class="hidden text-[11px] text-slate-500"></span>
                            </div>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="text-xs font-semibold text-slate-600">CPF/CNPJ</label>
                                    <div class="mt-1 flex gap-2">
                                        <input id="cnpj" type="text"
                                               value="{{ $cliente['cnpj'] ?? '' }}"
                                               class="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm"
                                               placeholder="Digite o CPF ou CNPJ">
                                        <button type="button" id="btnBuscarCnpj"
                                                class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                            Buscar CNPJ
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Razão social</label>
                                    <input id="razao_social" type="text"
                                           value="{{ $cliente['razao_social'] ?? '' }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Contato</label>
                                    <input id="contato" type="text"
                                           value="{{ $cliente['contato'] ?? '' }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-600">Telefone</label>
                                    <input id="telefone" type="text"
                                           value="{{ $cliente['telefone'] ?? '' }}"
                                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="text-sm font-semibold text-slate-900">Logos</div>
                            <p class="mt-1 text-xs text-slate-500">O preview e a impressão usam as mesmas imagens.</p>

                            <div class="mt-4 space-y-4">
                                <div>
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="text-xs font-semibold text-slate-600">Logo do cliente</label>
                                        <button type="button" id="clienteLogoRemove"
                                                class="text-xs font-semibold text-rose-600 hover:text-rose-700">
                                            Remover
                                        </button>
                                    </div>
                                    <input id="clienteLogoInput" type="file" accept="image/*"
                                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="text-xs font-semibold text-slate-600">Logo da FORMED</label>
                                        <button type="button" id="formedLogoRemove"
                                                class="text-xs font-semibold text-rose-600 hover:text-rose-700">
                                            Remover
                                        </button>
                                    </div>
                                    <input id="formedLogoInput" type="file" accept="image/*"
                                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                </div>

                                <div>
                                    <div class="flex items-center justify-between gap-3">
                                        <label class="text-xs font-semibold text-slate-600">Imagem de capa</label>
                                        <button type="button" id="coverImageRemove"
                                                class="text-xs font-semibold text-rose-600 hover:text-rose-700">
                                            Remover
                                        </button>
                                    </div>
                                    <input id="coverImageInput" type="file" accept="image/*"
                                           class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                                </div>
                            </div>
                        </section>
                    </div>
                </aside>

                <section>
                    <div class="rounded-[30px] border border-slate-200/80 bg-white/80 p-3 shadow-sm backdrop-blur sm:p-4">
                        <div class="mb-4 flex flex-col gap-2 border-b border-slate-200 px-2 pb-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">Pré-visualização da apresentação</div>
                                <p class="mt-1 text-xs text-slate-500">Este bloco mostra como o PDF será gerado para o cliente.</p>
                            </div>
                        </div>

                        @include('comercial.apresentacao.partials.documento-gamma', ['printMode' => false])
                    </div>
                </section>
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
                const formedLogoInput = document.getElementById('formedLogoInput');
                const formedLogoHeader = document.getElementById('formedLogoHeader');
                const formedLogoRemoveButton = document.getElementById('formedLogoRemove');
                const formedUploadUrl = @json(route('comercial.apresentacao.logo.formed'));
                const formedRemoveUrl = @json(route('comercial.apresentacao.logo.formed.destroy'));
                const coverImageInput = document.getElementById('coverImageInput');
                const coverImageRemoveButton = document.getElementById('coverImageRemove');
                const coverUploadUrl = @json(route('comercial.apresentacao.cover'));
                const coverRemoveUrl = @json(route('comercial.apresentacao.cover.destroy'));
                const previewTargets = {
                    razao_social: Array.from(document.querySelectorAll('[data-preview-field="razao_social"], #view_razao_social')),
                    cnpj: Array.from(document.querySelectorAll('[data-preview-field="cnpj"], #view_cnpj')),
                    contato: Array.from(document.querySelectorAll('[data-preview-field="contato"], #view_contato')),
                    telefone: Array.from(document.querySelectorAll('[data-preview-field="telefone"], #view_telefone')),
                };

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

                function maskDocumento(value) {
                    const digits = String(value || '').replace(/\D+/g, '').slice(0, 14);
                    if (digits.length <= 11) {
                        if (digits.length <= 3) return digits;
                        if (digits.length <= 6) return `${digits.slice(0, 3)}.${digits.slice(3)}`;
                        if (digits.length <= 9) return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6)}`;
                        return `${digits.slice(0, 3)}.${digits.slice(3, 6)}.${digits.slice(6, 9)}-${digits.slice(9)}`;
                    }
                    if (digits.length <= 2) return digits;
                    if (digits.length <= 5) return `${digits.slice(0, 2)}.${digits.slice(2)}`;
                    if (digits.length <= 8) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5)}`;
                    if (digits.length <= 12) return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8)}`;
                    return `${digits.slice(0, 2)}.${digits.slice(2, 5)}.${digits.slice(5, 8)}/${digits.slice(8, 12)}-${digits.slice(12)}`;
                }

                function maskTelefone(value) {
                    const digits = String(value || '').replace(/\D+/g, '').slice(0, 11);
                    if (digits.length <= 2) return digits;
                    if (digits.length <= 6) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
                    if (digits.length <= 10) return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
                    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
                }

                function syncPreview() {
                    if (razao) previewTargets.razao_social.forEach((el) => el.textContent = razao.value || '—');
                    if (cnpj) previewTargets.cnpj.forEach((el) => el.textContent = cnpj.value || '—');
                    if (contato) previewTargets.contato.forEach((el) => el.textContent = contato.value || '—');
                    if (telefone) previewTargets.telefone.forEach((el) => el.textContent = telefone.value || '—');
                }

                if (cnpj) {
                    cnpj.value = maskDocumento(cnpj.value);
                    cnpj.addEventListener('input', () => {
                        cnpj.value = maskDocumento(cnpj.value);
                        syncPreview();
                    });
                }

                if (telefone) {
                    telefone.value = maskTelefone(telefone.value);
                    telefone.addEventListener('input', () => {
                        telefone.value = maskTelefone(telefone.value);
                        syncPreview();
                    });
                }

                [cnpj, razao, contato, telefone].forEach((el) => {
                    el?.addEventListener('input', syncPreview);
                });

                btnBuscar?.addEventListener('click', async () => {
                    clearMsg();
                    const digits = String(cnpj?.value || '').replace(/\D+/g, '');
                    if (!digits) return setMsg('err', 'Informe um CPF ou CNPJ.');
                    if (digits.length === 11) {
                        return setMsg('err', 'Busca automática disponível apenas para CNPJ. Para CPF, preencha os dados manualmente.');
                    }
                    if (digits.length !== 14) {
                        return setMsg('err', 'Informe um CPF ou CNPJ válido.');
                    }

                    btnBuscar.disabled = true;
                    btnBuscar.textContent = 'Buscando...';

                    try {
                        const url = @json(route('comercial.clientes.consulta-cnpj', ['cnpj' => '__CNPJ__']))
                            .replace('__CNPJ__', encodeURIComponent(digits));
                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                        const json = await res.json().catch(() => ({}));

                        if (!res.ok) {
                            return setMsg('err', json?.error || 'Falha ao consultar CNPJ.');
                        }

                        if (razao && json?.razao_social) razao.value = json.razao_social;
                        if (contato && (json?.contato || json?.nome_fantasia)) {
                            contato.value = json.contato || json.nome_fantasia;
                        }
                        if (telefone && (json?.telefone || json?.telefone1 || json?.telefone2)) {
                            telefone.value = maskTelefone(json.telefone || json.telefone1 || json.telefone2);
                        }
                        syncPreview();
                        setMsg('ok', 'Dados preenchidos com sucesso.');
                    } catch (error) {
                        console.error(error);
                        setMsg('err', 'Falha ao consultar CNPJ.');
                    } finally {
                        btnBuscar.disabled = false;
                        btnBuscar.textContent = 'Buscar';
                    }
                });

                logoInput?.addEventListener('change', async () => {
                    const file = logoInput.files?.[0];
                    if (!file || !logoPreview) return;

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
                                Accept: 'application/json',
                            },
                            body: formData,
                        });
                    } catch (error) {
                        console.error(error);
                    }
                });

                logoRemoveButton?.addEventListener('click', async () => {
                    if (logoPreview) {
                        logoPreview.src = '';
                        logoPreview.classList.add('hidden');
                    }
                    if (logoInput) logoInput.value = '';

                    try {
                        await fetch(logoRemoveUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                Accept: 'application/json',
                            },
                        });
                    } catch (error) {
                        console.error(error);
                    }
                });

                formedLogoInput?.addEventListener('change', async () => {
                    const file = formedLogoInput.files?.[0];
                    if (!file) return;

                    if (formedLogoHeader) {
                        const reader = new FileReader();
                        reader.onload = () => {
                            formedLogoHeader.src = String(reader.result || '');
                            formedLogoHeader.classList.remove('hidden');
                        };
                        reader.readAsDataURL(file);
                    }

                    const formData = new FormData();
                    formData.append('logo', file);

                    try {
                        await fetch(formedUploadUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                Accept: 'application/json',
                            },
                            body: formData,
                        });
                    } catch (error) {
                        console.error(error);
                    }
                });

                formedLogoRemoveButton?.addEventListener('click', async () => {
                    if (formedLogoHeader) {
                        formedLogoHeader.src = '';
                        formedLogoHeader.classList.add('hidden');
                    }
                    if (formedLogoInput) formedLogoInput.value = '';

                    try {
                        await fetch(formedRemoveUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                Accept: 'application/json',
                            },
                        });
                    } catch (error) {
                        console.error(error);
                    }
                });

                coverImageInput?.addEventListener('change', async () => {
                    const file = coverImageInput.files?.[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('image', file);

                    try {
                        await fetch(coverUploadUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                Accept: 'application/json',
                            },
                            body: formData,
                        });

                        window.location.reload();
                    } catch (error) {
                        console.error(error);
                    }
                });

                coverImageRemoveButton?.addEventListener('click', async () => {
                    if (coverImageInput) coverImageInput.value = '';

                    try {
                        await fetch(coverRemoveUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                Accept: 'application/json',
                            },
                        });

                        window.location.reload();
                    } catch (error) {
                        console.error(error);
                    }
                });
            })();
        </script>
    @endpush
@endsection
