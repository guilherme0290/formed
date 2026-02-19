@php($fornecedor = $fornecedor ?? null)

<div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
    <div class="flex items-center justify-between gap-3">
        <div>
            <div class="text-sm font-semibold text-slate-900">Status do fornecedor</div>
            <div class="text-xs text-slate-500">Defina se este fornecedor está disponível para lançamento.</div>
        </div>
        <x-toggle-ativo
            name="ativo"
            :checked="(bool) old('ativo', $fornecedor?->ativo ?? true)"
            on-label="Ativo"
            off-label="Inativo"
            text-class="text-sm font-medium text-slate-700"
        />
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 space-y-4">
    <div>
        <h3 class="text-sm font-semibold text-slate-900">Informações gerais</h3>
        <p class="text-xs text-slate-500">Dados principais e contato do fornecedor.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="text-xs font-semibold text-slate-600">Razão social</label>
            <input type="text" name="razao_social" value="{{ old('razao_social', $fornecedor->razao_social ?? '') }}" required
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Nome fantasia</label>
            <input type="text" name="nome_fantasia" value="{{ old('nome_fantasia', $fornecedor->nome_fantasia ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Tipo pessoa</label>
            <select name="tipo_pessoa" class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
                <option value="PJ" @selected(old('tipo_pessoa', $fornecedor->tipo_pessoa ?? 'PJ') === 'PJ')>PJ</option>
                <option value="PF" @selected(old('tipo_pessoa', $fornecedor->tipo_pessoa ?? 'PJ') === 'PF')>PF</option>
            </select>
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">CPF/CNPJ</label>
            <input type="text" name="cpf_cnpj" value="{{ old('cpf_cnpj', $fornecedor->cpf_cnpj ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">E-mail</label>
            <input type="email" name="email" value="{{ old('email', $fornecedor->email ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Telefone</label>
            <input type="text" name="telefone" value="{{ old('telefone', $fornecedor->telefone ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div class="md:col-span-2">
            <label class="text-xs font-semibold text-slate-600">Contato</label>
            <input type="text" name="contato_nome" value="{{ old('contato_nome', $fornecedor->contato_nome ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>
    </div>
</div>

<div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 space-y-4">
    <div>
        <h3 class="text-sm font-semibold text-slate-900">Endereço</h3>
        <p class="text-xs text-slate-500">Localização do fornecedor para referência fiscal e operacional.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="text-xs font-semibold text-slate-600">CEP</label>
            <input type="text" name="cep" value="{{ old('cep', $fornecedor->cep ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Logradouro</label>
            <input type="text" name="logradouro" value="{{ old('logradouro', $fornecedor->logradouro ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Número</label>
            <input type="text" name="numero" value="{{ old('numero', $fornecedor->numero ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Complemento</label>
            <input type="text" name="complemento" value="{{ old('complemento', $fornecedor->complemento ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Bairro</label>
            <input type="text" name="bairro" value="{{ old('bairro', $fornecedor->bairro ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">Cidade</label>
            <input type="text" name="cidade" value="{{ old('cidade', $fornecedor->cidade ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
        </div>

        <div>
            <label class="text-xs font-semibold text-slate-600">UF</label>
            <input type="text" name="uf" maxlength="2" value="{{ old('uf', $fornecedor->uf ?? '') }}"
                   class="mt-1 w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm uppercase" />
        </div>
    </div>
</div>
