@php($fornecedor = $fornecedor ?? null)

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="text-xs font-semibold text-slate-600">Razão social</label>
        <input type="text" name="razao_social" value="{{ old('razao_social', $fornecedor->razao_social ?? '') }}" required
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">Nome fantasia</label>
        <input type="text" name="nome_fantasia" value="{{ old('nome_fantasia', $fornecedor->nome_fantasia ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">Tipo pessoa</label>
        <select name="tipo_pessoa" class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm">
            <option value="PJ" @selected(old('tipo_pessoa', $fornecedor->tipo_pessoa ?? 'PJ') === 'PJ')>PJ</option>
            <option value="PF" @selected(old('tipo_pessoa', $fornecedor->tipo_pessoa ?? 'PJ') === 'PF')>PF</option>
        </select>
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">CPF/CNPJ</label>
        <input type="text" name="cpf_cnpj" value="{{ old('cpf_cnpj', $fornecedor->cpf_cnpj ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">E-mail</label>
        <input type="email" name="email" value="{{ old('email', $fornecedor->email ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">Telefone</label>
        <input type="text" name="telefone" value="{{ old('telefone', $fornecedor->telefone ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">Contato</label>
        <input type="text" name="contato_nome" value="{{ old('contato_nome', $fornecedor->contato_nome ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">CEP</label>
        <input type="text" name="cep" value="{{ old('cep', $fornecedor->cep ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">Logradouro</label>
        <input type="text" name="logradouro" value="{{ old('logradouro', $fornecedor->logradouro ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">Número</label>
        <input type="text" name="numero" value="{{ old('numero', $fornecedor->numero ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">Complemento</label>
        <input type="text" name="complemento" value="{{ old('complemento', $fornecedor->complemento ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">Bairro</label>
        <input type="text" name="bairro" value="{{ old('bairro', $fornecedor->bairro ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">Cidade</label>
        <input type="text" name="cidade" value="{{ old('cidade', $fornecedor->cidade ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm" />
    </div>

    <div>
        <label class="text-xs font-semibold text-slate-600">UF</label>
        <input type="text" name="uf" maxlength="2" value="{{ old('uf', $fornecedor->uf ?? '') }}"
               class="w-full rounded-lg border-slate-200 bg-white text-slate-900 text-sm uppercase" />
    </div>
</div>

<label class="inline-flex items-center gap-2 text-sm text-slate-700">
    <input type="checkbox" name="ativo" value="1" class="rounded border-slate-300"
           @checked(old('ativo', $fornecedor?->ativo ?? true))>
    Fornecedor ativo
</label>
