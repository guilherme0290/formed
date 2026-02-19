<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaPagarBaixa extends Model
{
    protected $table = 'contas_pagar_baixas';

    protected $fillable = [
        'conta_pagar_id',
        'conta_pagar_item_id',
        'empresa_id',
        'fornecedor_id',
        'valor',
        'pago_em',
        'meio_pagamento',
        'observacao',
        'comprovante_path',
        'comprovante_nome',
        'comprovante_mime',
        'comprovante_tamanho',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'pago_em' => 'date',
        'comprovante_tamanho' => 'integer',
    ];

    public function contaPagar(): BelongsTo
    {
        return $this->belongsTo(ContaPagar::class, 'conta_pagar_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ContaPagarItem::class, 'conta_pagar_item_id');
    }
}
