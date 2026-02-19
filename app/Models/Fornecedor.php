<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fornecedor extends Model
{
    protected $table = 'fornecedores';

    protected $fillable = [
        'empresa_id',
        'razao_social',
        'nome_fantasia',
        'tipo_pessoa',
        'cpf_cnpj',
        'email',
        'telefone',
        'contato_nome',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'uf',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function contasPagar(): HasMany
    {
        return $this->hasMany(ContaPagar::class, 'fornecedor_id');
    }

    public function contasPagarItens(): HasMany
    {
        return $this->hasMany(ContaPagarItem::class, 'fornecedor_id');
    }
}
