@php
    $hero = $layout['hero'] ?? [];
    $desafios = $layout['desafios'] ?? [];
    $solucoes = $layout['solucoes'] ?? [];
    $palestras = $layout['palestras'] ?? [];
    $processo = $layout['processo'] ?? [];
    $investimento = $layout['investimento'] ?? [];
    $unidade = $layout['unidade'] ?? [];
    $contatoFinal = $layout['contato_final'] ?? [];

    $splitLines = static function (?string $text) {
        return collect(preg_split("/\r\n|\n|\r/", (string) $text))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();
    };
    $formatPhone = static function ($value) {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6, 4));
        }

        return trim((string) $value) !== '' ? (string) $value : null;
    };

    $desafiosItems = collect($desafios['items'] ?? [])
        ->filter(fn ($item) => ($item['active'] ?? true) !== false)
        ->filter(fn ($item) => filled($item['title'] ?? null))
        ->values();
    $desafiosPages = $desafiosItems->chunk(5)->values();

    $solucoesCards = collect($solucoes['cards'] ?? [])
        ->filter(fn ($item) => ($item['active'] ?? true) !== false)
        ->filter(fn ($item) => filled($item['title'] ?? null))
        ->values();
    $solucoesPages = $solucoesCards->chunk(10)->values();

    $palestrasItems = collect($palestras['items'] ?? [])
        ->filter(fn ($item) => filled($item['title'] ?? null))
        ->values();
    $palestrasPages = $palestrasItems->chunk(12)->values();

    $processoItems = collect($processo['items'] ?? [])
        ->filter(fn ($item) => ($item['active'] ?? true) !== false)
        ->filter(fn ($item) => filled($item['title'] ?? null))
        ->values();
    $processoPages = $processoItems->chunk(8)->values();

    $investmentCards = collect($investimento['cards'] ?? [])
        ->filter(fn ($item) => ($item['active'] ?? true) !== false)
        ->filter(fn ($item) => filled($item['title'] ?? null))
        ->values();
    $investmentPrimaryCards = $investmentCards->take(1)->values();
    $investmentSecondaryCards = $investmentCards->slice(1)->values();
    $investmentAsoItems = $splitLines($investimento['aso_items_text'] ?? '');

    $heroTitle = $hero['title'] ?? $tituloSegmento;
    $heroBadge = $hero['badge'] ?? 'Apresentação comercial';
    $heroSubtitle = $hero['subtitle'] ?? 'Sua obra não pode parar!';
    $heroDescription = trim((string) ($hero['description'] ?? ''));
    $desafiosBadge = $desafios['badge'] ?? 'Desafio do setor';
    $solucoesBadge = $solucoes['badge'] ?? 'Nossas soluções';
    $solucoesDescription = trim((string) ($solucoes['description'] ?? ''));
    $palestrasBadge = $palestras['badge'] ?? 'Calendário anual';
    $processoBadge = $processo['badge'] ?? 'Fluxo de atendimento';
    $investimentoBadge = $investimento['badge'] ?? 'Capacitação';

    $formedLogoSrc = $logoFormedData ?: asset('assets/apresentacao/construcao-civil/formed-logo.avif');
    $clienteLogoSrc = $clienteLogoData ?: null;
    $hasFormedLogo = filled($formedLogoSrc);
    $hasClienteLogo = filled($clienteLogoSrc);
    $obraImageSrc = $coverImageData ?: asset('assets/apresentacao/construcao-civil/obra-capa.avif');

    $unidadeBadge = $unidade['badge'] ?? 'Atendimento';
    $unidadeTitle = $unidade['title'] ?? 'Unidade de Fácil Acesso';
    $unidadeName = $unidade['name'] ?? 'Vila Mariana';
    $unidadeAddress = $unidade['address'] ?? "Rua Vergueiro, 1922\nMetrô Ana Rosa\nSão Paulo/SP";
    $unidadeSchedule = $unidade['schedule'] ?? 'Segunda a sexta das 7h às 14h e aos sábados sob agendamento';
    $unidadeHighlightTitle = $unidade['highlight_title'] ?? 'Atendimento In Company';
    $unidadeHighlightDescription = $unidade['highlight_description'] ?? 'Levamos toda a equipe até sua empresa para maior comodidade e agilidade! Fale com nosso comercial.';

    $contatoBadge = $contatoFinal['badge'] ?? 'Contato e próximos passos';
    $contatoTitle = $contatoFinal['title'] ?? 'Contato e Próximos Passos';
    $contatoDescription = $contatoFinal['description'] ?? 'Estamos prontos para otimizar a saúde ocupacional da sua empresa. Entre em contato hoje mesmo para agendar uma consulta e descobrir como podemos ser seu parceiro estratégico.';
    $contatoPhone = $responsavelApresentacao['telefone'] ?? null;
    $contatoEmail = $responsavelApresentacao['email'] ?? ($contatoFinal['email'] ?? 'gestao@formedseg.com.br');
    $contatoAddress = $contatoFinal['address'] ?? "Rua Vergueiro, 1922 - Vila Mariana\nSão Paulo/SP";
    $contatoSchedule = $contatoFinal['schedule'] ?? "Segunda a Sexta: 7h - 14h\nSábados: 9h - 11h";
    $contatoSite = $contatoFinal['site'] ?? 'www.formedseg.com.br';
    $contatoCta = $contatoFinal['cta_label'] ?? 'Fale conosco via WhatsApp';
    $clienteRazaoSocial = $cliente['razao_social'] ?? '—';
    $clienteCnpj = $cliente['cnpj'] ?? '—';
    $responsavelNome = $responsavelApresentacao['name'] ?? '—';
    $responsavelEmail = $responsavelApresentacao['email'] ?? '—';
    $responsavelTelefone = $formatPhone($responsavelApresentacao['telefone'] ?? null);
    $contatoPhone = $formatPhone($contatoPhone);
    $clienteTelefone = $cliente['telefone'] ?? '—';

    $pages = collect();

    if (($hero['enabled'] ?? true) !== false) {
        $pages->push(['key' => 'cover']);
    }

    if (($desafios['enabled'] ?? true) !== false) {
        foreach ($desafiosPages as $index => $items) {
            $pages->push(['key' => 'desafios', 'items' => $items, 'index' => $index]);
        }
    }

    if (($solucoes['enabled'] ?? true) !== false) {
        foreach ($solucoesPages as $index => $items) {
            $pages->push(['key' => 'solucoes', 'items' => $items, 'index' => $index]);
        }
    }

    if (($palestras['enabled'] ?? true) !== false) {
        foreach ($palestrasPages as $index => $items) {
            $pages->push(['key' => 'palestras', 'items' => $items, 'index' => $index]);
        }
    }

    if (($processo['enabled'] ?? true) !== false) {
        foreach ($processoPages as $index => $items) {
            $pages->push(['key' => 'processo', 'items' => $items, 'index' => $index]);
        }
    }

    if (($investimento['enabled'] ?? true) !== false) {
        $pages->push(['key' => 'investimento-principal', 'items' => $investmentPrimaryCards]);

        if ($investmentSecondaryCards->isNotEmpty()) {
            $pages->push(['key' => 'investimento-complementar', 'items' => $investmentSecondaryCards]);
        }
    }

    if (($unidade['enabled'] ?? true) !== false) {
        $pages->push(['key' => 'unidade']);
    }

    if (($contatoFinal['enabled'] ?? true) !== false) {
        $pages->push(['key' => 'contato']);
    }
@endphp

<style>
    .gamma-book,
    .gamma-book *,
    .gamma-book *::before,
    .gamma-book *::after {
        box-sizing: border-box;
    }

    .gamma-book {
        display: grid;
        gap: 1.25rem;
        justify-items: center;
    }

    .gamma-page {
        width: min(100%, 1380px, calc(100dvw - 4rem));
        aspect-ratio: 297 / 210;
        background: linear-gradient(180deg, #cfe0ff 0%, #e9f1ff 100%);
        border-radius: 28px;
        box-shadow: 0 28px 80px rgba(15, 23, 42, 0.14);
        overflow: hidden;
        break-after: page;
        page-break-after: always;
    }

    .gamma-page:last-child {
        break-after: auto;
        page-break-after: auto;
    }

    .gamma-frame {
        height: 100%;
        padding: 18px;
    }

    .gamma-card {
        height: 100%;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.84);
        border: 1px solid rgba(148, 163, 184, 0.18);
        padding: 28px;
        display: flex;
        flex-direction: column;
        gap: 20px;
        position: relative;
        overflow: hidden;
    }

    .gamma-card::before {
        content: "";
        position: absolute;
        width: 260px;
        height: 260px;
        border-radius: 999px;
        top: -110px;
        right: -80px;
        background: rgba(96, 165, 250, 0.16);
    }

    .gamma-shell {
        position: relative;
        z-index: 1;
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .gamma-badge {
        display: inline-flex;
        width: fit-content;
        padding: 8px 14px;
        border-radius: 999px;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
    }

    .gamma-title {
        margin: 0;
        font-size: clamp(2.4rem, 4vw, 4rem);
        line-height: 0.95;
        letter-spacing: -0.06em;
        font-weight: 900;
        color: #0f172a;
    }

    .gamma-section-title {
        margin: 0;
        font-size: clamp(2rem, 3vw, 3rem);
        line-height: 0.98;
        letter-spacing: -0.05em;
        font-weight: 900;
        color: #0f172a;
    }

    .gamma-copy {
        color: #475569;
        font-size: 0.98rem;
        line-height: 1.55;
        white-space: pre-line;
    }

    .gamma-cover-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
        gap: 24px;
        height: 100%;
    }

    .gamma-cover-copy {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 26px;
        padding: 8px 10px 8px 6px;
    }

    .gamma-cover-client-meta {
        margin-top: 40px;
    }

    .gamma-logos {
        display: flex;
        align-items: center;
        gap: 28px;
        flex-wrap: wrap;
    }

    .gamma-logo {
        max-width: 360px;
        max-height: 150px;
        object-fit: contain;
    }

    .gamma-divider {
        width: 1px;
        height: 74px;
        background: rgba(59, 130, 246, 0.3);
    }

    .gamma-cover-art {
        border-radius: 24px;
        background:
            linear-gradient(180deg, rgba(15, 23, 42, 0.08), rgba(15, 23, 42, 0.22)),
            url("{{ $obraImageSrc }}") center/cover no-repeat;
        min-height: 100%;
    }

    .gamma-grid-5 {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 12px;
    }

    .gamma-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .gamma-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
    }

    .gamma-grid-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .gamma-block-card {
        min-height: 0;
        border-radius: 22px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(239, 246, 255, 0.98));
        border: 1px solid rgba(147, 197, 253, 0.8);
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        box-shadow: 0 14px 28px rgba(59, 130, 246, 0.08);
    }

    .gamma-block-card--challenge {
        padding: 0;
        overflow: hidden;
        min-height: 218px;
        background: rgba(255, 255, 255, 0.94);
        border: 2px solid #b6cfe8;
        border-radius: 18px;
        box-shadow: none;
    }

    .gamma-block-card--challenge .gamma-number {
        width: 100%;
        height: auto;
        border-radius: 0;
        padding: 10px 12px;
        background: #bcd3ea;
        color: #1f2e63;
        font-size: 0.95rem;
        justify-content: center;
    }

    .gamma-block-card__body {
        padding: 16px 18px 18px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .gamma-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 12px;
        background: #2563eb;
        color: #fff;
        font-size: 0.9rem;
        font-weight: 800;
        flex: none;
    }

    .gamma-card-title {
        font-size: 1rem;
        line-height: 1.28;
        font-weight: 800;
        color: #111827;
        white-space: pre-line;
    }

    .gamma-card-copy {
        color: #526076;
        font-size: 0.9rem;
        line-height: 1.45;
        white-space: pre-line;
    }

    .gamma-card-value {
        font-size: 1.5rem;
        line-height: 1;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.04em;
    }

    .gamma-list {
        display: grid;
        gap: 8px;
    }

    .gamma-list-row {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 10px 12px;
        border-radius: 16px;
        background: rgba(219, 234, 254, 0.46);
        border: 1px solid rgba(147, 197, 253, 0.6);
    }

    .gamma-list-dot {
        width: 8px;
        height: 8px;
        margin-top: 7px;
        border-radius: 999px;
        background: #2563eb;
        flex: none;
    }

    .gamma-annual-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        grid-template-rows: repeat(6, minmax(0, 1fr));
        grid-auto-flow: column;
        gap: 10px 12px;
        flex: 1;
        min-height: 0;
        align-content: start;
    }

    .gamma-annual-item {
        display: grid;
        grid-template-columns: 36px minmax(110px, 150px) minmax(0, 1fr);
        gap: 10px;
        align-items: center;
        padding: 10px 12px;
        border-radius: 16px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(239, 246, 255, 0.9));
        border: 1px solid rgba(147, 197, 253, 0.7);
        min-height: 0;
    }

    .gamma-annual-month {
        font-size: 0.82rem;
        line-height: 1.25;
        font-weight: 800;
        color: #1e3a8a;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        text-align: center;
    }

    .gamma-annual-topic {
        font-size: 0.82rem;
        line-height: 1.3;
        color: #334155;
        white-space: pre-line;
        text-align: center;
    }

    .gamma-annual-item .gamma-number {
        justify-self: center;
        align-self: center;
    }

    .gamma-annual-dot {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        background: #2563eb;
        justify-self: center;
        align-self: center;
        box-shadow: 0 0 0 4px rgba(219, 234, 254, 0.9);
    }

    .gamma-process-grid {
        position: relative;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 58px;
        row-gap: 14px;
        padding-inline: 12px;
    }

    .gamma-process-grid::before {
        content: "";
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 2px;
        transform: translateX(-50%);
        background: #bfd7ff;
    }

    .gamma-process-card {
        position: relative;
        min-height: 126px;
        padding: 16px;
        border-radius: 20px;
        background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
        border: 1px solid rgba(96, 165, 250, 0.8);
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .gamma-process-card::after {
        content: "";
        position: absolute;
        right: -29px;
        top: 50%;
        width: 28px;
        height: 2px;
        background: #93c5fd;
    }

    .gamma-process-card::before {
        content: "";
        position: absolute;
        top: calc(50% - 6px);
        right: -35px;
        width: 12px;
        height: 12px;
        border-radius: 999px;
        background: #60a5fa;
        box-shadow: 0 0 0 4px rgba(219, 234, 254, 0.92);
    }

    .gamma-process-card:nth-child(even)::after {
        right: auto;
        left: -29px;
    }

    .gamma-process-card:nth-child(even)::before {
        right: auto;
        left: -35px;
    }

    .gamma-process-card:nth-child(odd) {
        margin-right: 8px;
    }

    .gamma-process-card:nth-child(even) {
        margin-left: 8px;
    }

    .gamma-investment-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr);
        gap: 16px;
        min-height: 0;
        flex: 1;
        align-items: stretch;
    }

    .gamma-investment-main {
        padding: 22px;
        border-radius: 18px;
        background: #fff;
        border: 1px solid rgba(96, 165, 250, 0.8);
        display: flex;
        flex-direction: column;
        gap: 18px;
        min-height: 0;
        height: 100%;
    }

    .gamma-investment-side {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .gamma-investment-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        align-content: stretch;
        grid-auto-rows: minmax(0, 1fr);
    }

    .gamma-investment-grid--compact {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
    }

    .gamma-investment-grid--dense {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .gamma-investment-main--spread {
        justify-content: space-between;
    }

    .gamma-investment-main--accent {
        background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%);
        border-color: rgba(96, 165, 250, 0.8);
        color: #1e3a8a;
    }

    .gamma-investment-main--accent .gamma-card-title,
    .gamma-investment-main--accent .gamma-card-value,
    .gamma-investment-main--accent .gamma-card-copy {
        color: #1e3a8a;
    }

    .gamma-investment-list {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-evenly;
        min-height: 0;
    }

    .gamma-nowrap {
        white-space: nowrap;
    }

    .gamma-investment-main--compact {
        padding: 16px;
        gap: 10px;
        border-radius: 16px;
        justify-content: center;
        text-align: center;
        align-items: center;
    }

    .gamma-investment-main--compact .gamma-card-title {
        font-size: 0.9rem;
        line-height: 1.2;
    }

    .gamma-investment-main--compact .gamma-card-value {
        font-size: 1rem !important;
        line-height: 1.05;
    }

    .gamma-investment-main--compact .gamma-card-copy {
        font-size: 0.8rem;
        line-height: 1.25;
    }

    .gamma-investment-main--compact .gamma-list {
        align-items: center;
    }

    .gamma-contact-grid {
        display: grid;
        grid-template-columns: 1.1fr 0.75fr;
        gap: 18px;
        align-items: stretch;
    }

    .gamma-contact-stack {
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
    }

    .gamma-contact-art {
        min-height: 100%;
        border-radius: 0 24px 24px 120px;
        background:
            linear-gradient(180deg, rgba(14, 116, 144, 0.18), rgba(15, 23, 42, 0.18)),
            url("{{ $obraImageSrc }}") center/cover no-repeat;
        border: 1px solid rgba(191, 219, 254, 0.95);
    }

    .gamma-meta-card {
        border-radius: 20px;
        background: #fff;
        border: 1px solid rgba(191, 219, 254, 0.9);
        padding: 16px;
    }

    .gamma-meta-label {
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .gamma-meta-value {
        margin-top: 10px;
        color: #0f172a;
        font-size: 0.94rem;
        font-weight: 700;
        line-height: 1.45;
        white-space: pre-line;
    }

    .gamma-highlight-card {
        min-height: 160px;
        border-radius: 24px;
        background: linear-gradient(180deg, #1d4ed8 0%, #60a5fa 100%);
        color: #fff;
        padding: 24px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 16px;
    }

    .gamma-cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 14px 24px;
        border-radius: 999px;
        background: #1d4ed8;
        color: #fff;
        font-size: 0.95rem;
        font-weight: 800;
        width: fit-content;
        min-width: 290px;
    }

    .gamma-unit-shell {
        text-align: center;
        align-items: center;
        justify-content: space-between;
    }

    .gamma-unit-copy {
        max-width: 760px;
        margin-inline: auto;
        display: grid;
        gap: 18px;
        justify-items: center;
    }

    .gamma-unit-address {
        font-size: clamp(1.3rem, 2.3vw, 2rem);
        line-height: 1.28;
        font-weight: 800;
        color: #1e3a8a;
        white-space: pre-line;
    }

    .gamma-unit-schedule {
        color: #334155;
        font-size: 1.05rem;
        line-height: 1.5;
    }

    .gamma-unit-highlight {
        width: min(100%, 1040px);
        min-height: 0;
        border-radius: 22px;
        background: #bfdbfe;
        padding: 22px 24px;
        text-align: left;
        color: #0f172a;
        border: 1px solid rgba(96, 165, 250, 0.55);
    }

    .gamma-challenge-shell {
        position: relative;
        gap: 22px;
        padding: 18px 22px 24px;
        border-radius: 30px;
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.92)),
            url("{{ $obraImageSrc }}") center/cover no-repeat;
        border: 1px solid rgba(182, 207, 232, 0.95);
        overflow: hidden;
    }

    .gamma-challenge-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.74);
        z-index: 0;
    }

    .gamma-challenge-shell::after {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(180deg, rgba(188, 211, 234, 0.12), rgba(188, 211, 234, 0.12)),
            url("{{ $obraImageSrc }}") center/cover no-repeat;
        opacity: 0.12;
        z-index: 0;
    }

    .gamma-challenge-shell > * {
        position: relative;
        z-index: 1;
    }

    .gamma-challenge-badge {
        display: none;
    }

    .gamma-challenge-title {
        margin: 0;
        font-size: clamp(2.2rem, 3.4vw, 3.25rem);
        line-height: 0.98;
        letter-spacing: -0.06em;
        font-weight: 900;
        color: #16255b;
    }

    .gamma-challenge-grid {
        width: 100%;
        margin-inline: auto;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px 18px;
        align-content: start;
    }

    .gamma-challenge-grid > :nth-child(4) {
        grid-column: 1;
    }

    .gamma-challenge-grid > :nth-child(5) {
        grid-column: 2;
    }

    .gamma-challenge-grid .gamma-block-card__body {
        padding: 16px 18px 18px;
        gap: 12px;
    }

    .gamma-challenge-grid .gamma-card-title {
        font-size: 1.06rem;
        line-height: 1.18;
        color: #2b3b71;
    }

    .gamma-challenge-grid .gamma-card-copy {
        font-size: 0.9rem;
        line-height: 1.48;
        color: #44557f;
    }

    .gamma-solutions-shell {
        gap: 24px;
        padding: 22px 28px 26px;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(182, 207, 232, 0.95);
    }

    .gamma-solutions-badge {
        display: none;
    }

    .gamma-solutions-title {
        margin: 0;
        font-size: clamp(2.15rem, 3.2vw, 3rem);
        line-height: 1;
        letter-spacing: -0.06em;
        font-weight: 900;
        color: #16255b;
    }

    .gamma-solutions-copy {
        max-width: 1120px;
        color: #44557f;
        font-size: 1rem;
        line-height: 1.6;
    }

    .gamma-solutions-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        align-content: start;
        align-items: start;
        grid-auto-rows: auto;
    }

    .gamma-solutions-grid--dense {
        grid-template-columns: repeat(5, minmax(0, 1fr));
        grid-template-rows: repeat(2, minmax(0, 1fr));
        gap: 10px;
        flex: 1;
        align-content: stretch;
        align-items: stretch;
    }

    .gamma-solutions-grid .gamma-block-card {
        min-height: 0;
        padding: 8px 8px 7px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.94);
        border: 2px solid #b6cfe8;
        box-shadow: none;
        gap: 4px;
    }

    .gamma-solutions-grid--dense .gamma-block-card {
        height: 100%;
        padding: 8px 8px 7px;
        border-radius: 14px;
        gap: 4px;
        justify-content: flex-start;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(241, 245, 255, 0.96));
    }

    .gamma-solutions-grid .gamma-card-title {
        font-size: 0.94rem;
        line-height: 1.08;
        color: #2b3b71;
        margin-bottom: 4px;
    }

    .gamma-solutions-grid--dense .gamma-card-title {
        font-size: 0.9rem;
        line-height: 1.02;
        margin-bottom: 5px;
    }

    .gamma-solutions-grid .gamma-card-copy {
        font-size: 0.82rem;
        line-height: 1.18;
        color: #44557f;
    }

    .gamma-solutions-grid--dense .gamma-card-copy {
        font-size: 0.78rem;
        line-height: 1.08;
    }

    .gamma-solutions-grid--duo .gamma-block-card {
        min-height: 260px;
        padding: 20px 18px 18px;
    }

    .gamma-solutions-grid--duo .gamma-card-title {
        font-size: 1.18rem;
        line-height: 1.16;
    }

    .gamma-solutions-grid--duo .gamma-card-copy {
        font-size: 0.92rem;
        line-height: 1.55;
    }

    .gamma-solutions-duo-panel {
        flex: 1;
        padding: 18px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.94);
        border: 2px solid #b6cfe8;
    }

    @media (max-width: 1100px) {
        .gamma-page {
            width: min(100%, calc(100dvw - 1rem));
        }

        .gamma-cover-layout,
        .gamma-investment-layout,
        .gamma-grid-5,
        .gamma-grid-4,
        .gamma-grid-3,
        .gamma-grid-2,
        .gamma-contact-grid,
        .gamma-process-grid,
        .gamma-investment-side,
        .gamma-investment-grid,
        .gamma-investment-grid--compact {
            grid-template-columns: 1fr;
        }

        .gamma-contact-grid {
            grid-template-columns: 1fr;
        }

        .gamma-divider {
            width: 120px;
            height: 1px;
        }

        .gamma-process-grid::before,
        .gamma-process-card::after {
            display: none;
        }

        .gamma-process-card::before {
            display: none;
        }

        .gamma-challenge-grid {
            width: 100%;
            grid-template-columns: 1fr;
        }

        .gamma-challenge-grid > :nth-child(4),
        .gamma-challenge-grid > :nth-child(5) {
            grid-column: auto;
        }

        .gamma-solutions-grid {
            grid-template-columns: 1fr;
        }
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 0;
        }

        html,
        body {
            width: 297mm;
            height: 210mm;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .gamma-page {
            width: 297mm;
            height: 210mm;
            margin: 0;
            border-radius: 0;
            box-shadow: none;
            aspect-ratio: auto;
        }

        .gamma-frame {
            padding: 0;
        }

        .gamma-card {
            border-radius: 0;
            padding: 6mm;
            height: 100%;
        }

        .gamma-book {
            display: block;
            gap: 0;
        }

        .gamma-page,
        .gamma-frame,
        .gamma-card,
        .gamma-shell {
            overflow: hidden !important;
        }

        .gamma-shell,
        .gamma-solutions-shell,
        .gamma-challenge-shell,
        .gamma-unit-shell {
            gap: 10px !important;
        }

        .gamma-title {
            font-size: 2.4rem;
        }

        .gamma-section-title,
        .gamma-solutions-title,
        .gamma-challenge-title {
            font-size: 1.85rem !important;
        }

        .gamma-copy,
        .gamma-solutions-copy,
        .gamma-card-copy,
        .gamma-annual-topic,
        .gamma-unit-address,
        .gamma-unit-schedule,
        .gamma-meta-value {
            font-size: 0.78rem !important;
            line-height: 1.2 !important;
        }

        .gamma-card-title,
        .gamma-annual-month,
        .gamma-meta-label {
            font-size: 0.84rem !important;
            line-height: 1.08 !important;
        }

        .gamma-card-value {
            font-size: 1.2rem !important;
        }

        .gamma-logos {
            gap: 18px;
        }

        .gamma-logo {
            max-width: 280px;
            max-height: 110px;
        }

        .gamma-divider {
            width: 1px !important;
            height: 56px;
        }

        .gamma-cover-layout,
        .gamma-investment-layout,
        .gamma-contact-grid {
            gap: 10px;
        }

        .gamma-cover-layout {
            display: grid !important;
            grid-template-columns: minmax(0, 1.2fr) minmax(76mm, 0.8fr) !important;
            align-items: stretch !important;
        }

        .gamma-cover-copy {
            min-width: 0 !important;
        }

        .gamma-cover-art {
            min-height: 100% !important;
            height: 100% !important;
            border-radius: 20px !important;
        }

        .gamma-logos {
            flex-wrap: nowrap !important;
        }

        .gamma-challenge-grid,
        .gamma-process-grid,
        .gamma-investment-grid,
        .gamma-annual-list {
            gap: 8px !important;
        }

        .gamma-challenge-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            align-content: start !important;
        }

        .gamma-solutions-grid {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 8px !important;
            align-content: start !important;
            align-items: stretch !important;
            grid-auto-rows: auto !important;
            width: 100% !important;
            flex: none !important;
        }

        .gamma-solutions-grid--dense {
            display: grid !important;
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            grid-template-rows: repeat(2, minmax(0, 1fr)) !important;
            gap: 8px !important;
            align-content: stretch !important;
            align-items: stretch !important;
            flex: 1 !important;
        }

    .gamma-solutions-grid .gamma-block-card,
    .gamma-solutions-grid--dense .gamma-block-card {
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
            height: 100% !important;
        min-height: 0 !important;
    }

    .gamma-solutions-grid .gamma-block-card,
    .gamma-solutions-grid--duo .gamma-block-card,
    .gamma-solutions-grid--dense .gamma-block-card,
    .gamma-solutions-duo-panel .gamma-block-card {
        justify-content: center;
        align-items: center;
        text-align: center;
    }

    .gamma-solutions-grid .gamma-card-title,
    .gamma-solutions-grid--duo .gamma-card-title,
    .gamma-solutions-grid--dense .gamma-card-title,
    .gamma-solutions-duo-panel .gamma-card-title,
    .gamma-solutions-grid .gamma-card-copy,
    .gamma-solutions-grid--duo .gamma-card-copy,
    .gamma-solutions-grid--dense .gamma-card-copy,
    .gamma-solutions-duo-panel .gamma-card-copy {
        width: 100%;
        text-align: center;
    }

    .gamma-block-card--challenge .gamma-block-card__body,
    .gamma-block-card--challenge .gamma-card-title,
    .gamma-block-card--challenge .gamma-card-copy {
        text-align: center;
    }

    .gamma-block-card--challenge .gamma-block-card__body {
        align-items: center;
        justify-content: center;
    }

        .gamma-process-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .gamma-investment-grid {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .gamma-investment-grid--dense {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }

        .gamma-annual-list {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            grid-template-rows: repeat(6, minmax(0, 1fr)) !important;
        }

        .gamma-process-card,
        .gamma-block-card,
        .gamma-investment-main,
        .gamma-annual-item,
        .gamma-meta-card,
        .gamma-highlight-card {
            padding: 10px !important;
            border-radius: 14px !important;
        }

        .gamma-solutions-grid--dense .gamma-block-card {
            padding: 8px !important;
        }

        .gamma-process-card {
            min-height: 0;
        }

        .gamma-process-grid {
            column-gap: 40px !important;
            row-gap: 6px !important;
            padding-inline: 6px !important;
        }

        .gamma-process-card {
            padding: 8px !important;
            gap: 6px !important;
        }

        .gamma-investment-main,
        .gamma-investment-main--compact {
            gap: 8px !important;
        }
    }
</style>

<article class="gamma-book">
    @foreach ($pages as $pageData)
        @php
            $page = $pageData['key'];
            $pageItems = $pageData['items'] ?? collect();
            $pageIndex = ($pageData['index'] ?? 0) + 1;
        @endphp

        @if ($page === 'cover')
            <section class="gamma-page">
                <div class="gamma-frame">
                    <div class="gamma-card">
                        <div class="gamma-shell gamma-cover-layout">
                            <div class="gamma-cover-copy">
                                <div class="gamma-logos">
                                    @if ($hasFormedLogo)
                                        <img id="formedLogoHeader" src="{{ $formedLogoSrc }}" alt="Logo FORMED" class="gamma-logo" onerror="this.classList.add('hidden')">
                                    @endif
                                    <span id="clienteLogoDivider" class="gamma-divider {{ $hasClienteLogo ? '' : 'hidden' }}" aria-hidden="true"></span>
                                    <img id="clienteLogoPreview"
                                         src="{{ $clienteLogoSrc }}"
                                         alt="Logo do cliente"
                                         class="gamma-logo {{ $hasClienteLogo ? '' : 'hidden' }}"
                                         onerror="this.classList.add('hidden')">
                                </div>

                                <div class="gamma-badge">{{ $heroBadge }}</div>
                                <h1 class="gamma-title">{{ $heroTitle }}</h1>
                                <div class="gamma-section-title" style="font-size: clamp(1.5rem, 2.4vw, 2.2rem);">{{ $heroSubtitle }}</div>
                                @if (filled($heroDescription))
                                    <div class="gamma-copy">{{ $heroDescription }}</div>
                                @endif

                                <div class="gamma-contact-grid gamma-cover-client-meta" style="grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px;">
                                    <div class="gamma-meta-card">
                                        <div class="gamma-meta-label">Cliente</div>
                                        <div class="gamma-meta-value" id="view_razao_social" data-preview-field="razao_social">{{ $clienteRazaoSocial }}</div>
                                    </div>
                                    <div class="gamma-meta-card">
                                        <div class="gamma-meta-label">CPF/CNPJ</div>
                                        <div class="gamma-meta-value" id="view_cnpj" data-preview-field="cnpj">{{ $clienteCnpj }}</div>
                                    </div>
                                    <div class="gamma-meta-card">
                                        <div class="gamma-meta-label">Telefone do cliente</div>
                                        <div class="gamma-meta-value" id="view_telefone" data-preview-field="telefone">{{ $clienteTelefone }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="gamma-cover-art" aria-hidden="true"></div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($page === 'desafios')
            <section class="gamma-page">
                <div class="gamma-frame">
                    <div class="gamma-card">
                        <div class="gamma-shell gamma-challenge-shell">
                            <div class="gamma-challenge-badge">{{ $desafiosBadge }}</div>
                            <h2 class="gamma-challenge-title">{{ $desafios['title'] ?? 'Desafio do Setor' }}</h2>

                            <div class="gamma-challenge-grid" style="flex: 1;">
                                @foreach ($pageItems as $index => $item)
                                    <div class="gamma-block-card gamma-block-card--challenge">
                                        <span class="gamma-number">{{ (($pageIndex - 1) * 5) + $index + 1 }}</span>
                                        <div class="gamma-block-card__body">
                                            <div class="gamma-card-title">{{ $item['title'] }}</div>
                                            <div class="gamma-card-copy">{{ $item['description'] ?? '' }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($page === 'solucoes')
            <section class="gamma-page">
                <div class="gamma-frame">
                    <div class="gamma-card">
                        <div class="gamma-shell gamma-solutions-shell">
                            <div class="gamma-solutions-badge">{{ $solucoesBadge }}</div>
                            <h2 class="gamma-solutions-title">{{ $solucoes['title'] ?? 'Nossas Soluções' }}</h2>
                            @if ($pageIndex === 1 && filled($solucoesDescription))
                                <div class="gamma-solutions-copy">{{ $solucoesDescription }}</div>
                            @endif

                            @if ($pageItems->count() <= 2)
                                <div class="gamma-solutions-duo-panel">
                                    <div class="gamma-solutions-grid gamma-solutions-grid--duo">
                                        @foreach ($pageItems as $card)
                                            <div class="gamma-block-card">
                                                <div class="gamma-card-title">{{ $card['title'] ?? '' }}</div>
                                                <div class="gamma-card-copy">{{ $card['description'] ?? '' }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="gamma-solutions-grid {{ $pageItems->count() > 6 ? 'gamma-solutions-grid--dense' : '' }}">
                                    @foreach ($pageItems as $card)
                                        <div class="gamma-block-card">
                                            <div class="gamma-card-title">{{ $card['title'] ?? '' }}</div>
                                            <div class="gamma-card-copy">{{ $card['description'] ?? '' }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($page === 'palestras')
            <section class="gamma-page">
                <div class="gamma-frame">
                    <div class="gamma-card">
                        <div class="gamma-shell">
                            <div class="gamma-badge">{{ $palestrasBadge }}</div>
                            <h2 class="gamma-section-title">{{ $palestras['title'] ?? 'Palestras conforme o calendário anual' }}</h2>

                            <div class="gamma-annual-list" style="flex: 1;">
                                @foreach ($pageItems as $index => $item)
                                    <div class="gamma-annual-item">
                                        <span class="gamma-annual-dot" aria-hidden="true"></span>
                                        <div class="gamma-annual-month">{{ $item['title'] }}</div>
                                        <div class="gamma-annual-topic">{{ $item['description'] ?? '' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($page === 'processo')
            <section class="gamma-page">
                <div class="gamma-frame">
                    <div class="gamma-card">
                        <div class="gamma-shell">
                            <div class="gamma-badge">{{ $processoBadge }}</div>
                            <h2 class="gamma-section-title">{{ $processo['title'] ?? 'Processo Simplificado' }}</h2>

                            <div class="gamma-process-grid" style="flex: 1;">
                                @foreach ($pageItems as $index => $item)
                                    <div class="gamma-process-card">
                                        <span class="gamma-number">{{ (($pageIndex - 1) * 8) + $index + 1 }}</span>
                                        <div class="gamma-card-title">{{ $item['title'] }}</div>
                                        <div class="gamma-card-copy">{{ $item['description'] ?? '' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($page === 'investimento-principal')
            <section class="gamma-page">
                <div class="gamma-frame">
                    <div class="gamma-card">
                        <div class="gamma-shell">
                            <div class="gamma-badge">{{ $investimentoBadge }}</div>
                            <h2 class="gamma-section-title">{{ $investimento['title'] ?? 'Investimento' }}</h2>

                            <div class="gamma-investment-layout">
                                <div class="gamma-investment-main gamma-investment-main--spread gamma-investment-main--accent">
                                    <div class="gamma-card-title">{{ $investimento['aso_title'] ?? 'ASO - TRABALHO EM ALTURA / ESPAÇO CONFINADO' }}</div>
                                    <div class="gamma-card-value">{{ $investimento['aso_price'] ?? 'R$ 240,00' }}</div>

                                    <div class="gamma-list gamma-investment-list" style="gap: 6px;">
                                        @foreach ($investmentAsoItems as $index => $item)
                                            <div class="gamma-card-copy" style="display: flex; gap: 12px; align-items: flex-start; background: transparent; border: 0; padding: 0;">
                                                <span style="display: inline-block; min-width: 18px; font-weight: 800; color: #2563eb;">{{ $index + 1 }}.</span>
                                                <span>{{ $item }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                @php $esocialCard = $pageItems->first(); @endphp
                                <div class="gamma-investment-main gamma-investment-main--spread gamma-investment-main--accent">
                                    @if ($esocialCard)
                                        <div class="gamma-card-title">{{ $esocialCard['title'] }}</div>
                                        @if (filled($esocialCard['description'] ?? null) || filled($esocialCard['value'] ?? null))
                                            <div class="gamma-card-copy gamma-nowrap">
                                                @if (filled($esocialCard['description'] ?? null))
                                                    {{ $esocialCard['description'] }}
                                                @endif
                                                @if (filled($esocialCard['value'] ?? null))
                                                    <strong>{{ filled($esocialCard['description'] ?? null) ? ': ' : '' }}{{ $esocialCard['value'] }}</strong>
                                                @endif
                                            </div>
                                        @endif
                                        @php $cardItems = $splitLines($esocialCard['items'] ?? null); @endphp
                                        @if ($cardItems->isNotEmpty())
                                            <div class="gamma-list gamma-investment-list" style="gap: 0;">
                                                @foreach ($cardItems as $item)
                                                    <div class="gamma-card-copy" style="padding: 12px 0; border-bottom: 1px solid rgba(96, 165, 250, 0.35);">{{ $item }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($page === 'investimento-complementar')
            <section class="gamma-page">
                <div class="gamma-frame">
                    <div class="gamma-card">
                        <div class="gamma-shell">
                            <div class="gamma-badge">{{ $investimentoBadge }}</div>
                            <h2 class="gamma-section-title">{{ $investimento['title'] ?? 'Investimento' }}</h2>

                            <div class="gamma-investment-grid gamma-investment-grid--dense" style="flex: 1;">
                                @foreach ($pageItems as $card)
                                    <div class="gamma-investment-main gamma-investment-main--compact">
                                        <div class="gamma-card-title">{{ $card['title'] }}</div>
                                        @if (filled($card['value'] ?? null))
                                            <div class="gamma-card-value">{{ $card['value'] }}</div>
                                        @endif
                                        @if (filled($card['description'] ?? null))
                                            <div class="gamma-card-copy">{{ $card['description'] }}</div>
                                        @endif
                                        @php $cardItems = $splitLines($card['items'] ?? null); @endphp
                                        @if ($cardItems->isNotEmpty())
                                            <div class="gamma-list" style="gap: 4px;">
                                                @foreach ($cardItems as $item)
                                                    <div class="gamma-card-copy" style="margin-top: -2px;">{{ $item }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($page === 'unidade')
            <section class="gamma-page">
                <div class="gamma-frame">
                    <div class="gamma-card">
                        <div class="gamma-shell gamma-unit-shell">
                            <div class="gamma-badge">{{ $unidadeBadge }}</div>
                            <div class="gamma-unit-copy">
                                <h2 class="gamma-section-title">{{ $unidadeTitle }}</h2>
                                <div class="gamma-card-title" style="font-size: clamp(1.9rem, 3vw, 3rem); color: #1e3a8a;">{{ $unidadeName }}</div>
                                <div class="gamma-unit-address">{{ $unidadeAddress }}</div>
                                <div class="gamma-unit-schedule"><strong>Horário de atendimento:</strong> {{ $unidadeSchedule }}</div>
                            </div>

                            <div class="gamma-unit-highlight">
                                <div class="gamma-card-title">{{ $unidadeHighlightTitle }}: <span style="font-weight: 600;">{{ $unidadeHighlightDescription }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($page === 'contato')
            <section class="gamma-page">
                <div class="gamma-frame">
                    <div class="gamma-card">
                        <div class="gamma-shell">
                            <div class="gamma-badge">{{ $contatoBadge }}</div>
                            <h2 class="gamma-section-title">{{ $contatoTitle }}</h2>
                            <div class="gamma-copy">{{ $contatoDescription }}</div>

                            <div class="gamma-contact-grid" style="flex: 1;">
                                <div class="gamma-contact-stack">
                                    <div class="gamma-meta-card">
                                        <div class="gamma-meta-label">Comercial Responsável</div>
                                        <div class="gamma-meta-value">{{ $responsavelNome }}</div>
                                    </div>
                                    <div class="gamma-meta-card">
                                        <div class="gamma-meta-label">Telefones</div>
                                        <div class="gamma-meta-value">{{ $contatoPhone }}</div>
                                    </div>
                                    <div class="gamma-meta-card">
                                        <div class="gamma-meta-label">E-mail</div>
                                        <div class="gamma-meta-value">{{ $contatoEmail }}</div>
                                    </div>
                                    <div class="gamma-meta-card">
                                        <div class="gamma-meta-label">Endereço Principal</div>
                                        <div class="gamma-meta-value">{{ $contatoAddress }}</div>
                                    </div>
                                    <div class="gamma-meta-card">
                                        <div class="gamma-meta-label">Horário de Funcionamento</div>
                                        <div class="gamma-meta-value">{{ $contatoSchedule }}</div>
                                    </div>
                                    <div class="gamma-copy" style="font-weight: 700;">Acesse nosso Site: {{ $contatoSite }}</div>
                                </div>

                                <div class="gamma-contact-art" aria-hidden="true"></div>
                            </div>

                            <div>
                                <span class="gamma-cta">{{ $contatoCta }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endforeach
</article>
