<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposta extends Model
{
    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'vendedor_id',
        'codigo',
        'forma_pagamento',
        'incluir_esocial',
        'esocial_qtd_funcionarios',
        'esocial_valor_mensal',
        'valor_total',
        'status',
        'observacoes',
    ];

    protected $casts = [
        'incluir_esocial'       => 'bool',
        'esocial_valor_mensal'  => 'decimal:2',
        'valor_total'           => 'decimal:2',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function itens()
    {
        return $this->hasMany(PropostaItens::class);
    }


}
