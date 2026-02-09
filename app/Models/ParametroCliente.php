<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParametroCliente extends Model
{
    protected $table = 'parametro_clientes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'vendedor_id',
        'forma_pagamento',
        'incluir_esocial',
        'esocial_qtd_funcionarios',
        'esocial_valor_mensal',
        'valor_total',
        'prazo_dias',
        'vencimento_servicos',
        'observacoes',
    ];

    protected $casts = [
        'incluir_esocial' => 'bool',
        'esocial_valor_mensal' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'prazo_dias' => 'int',
        'vencimento_servicos' => 'int',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ParametroClienteItem::class, 'parametro_cliente_id');
    }

    public function asoGrupos(): HasMany
    {
        return $this->hasMany(ParametroClienteAsoGrupo::class, 'parametro_cliente_id');
    }
}
