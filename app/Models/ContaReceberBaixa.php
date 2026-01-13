<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaReceberBaixa extends Model
{
    protected $table = 'contas_receber_baixas';

    protected $fillable = [
        'conta_receber_id',
        'conta_receber_item_id',
        'empresa_id',
        'cliente_id',
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

    public function contaReceber(): BelongsTo
    {
        return $this->belongsTo(ContaReceber::class, 'conta_receber_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ContaReceberItem::class, 'conta_receber_item_id');
    }
}
