<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendaItem extends Model
{
    protected $table = 'venda_itens';

    protected $fillable = [
        'venda_id',
        'servico_id',
        'descricao_snapshot',
        'preco_unitario_snapshot',
        'quantidade',
        'subtotal_snapshot',
    ];

    protected $casts = [
        'preco_unitario_snapshot' => 'decimal:2',
        'subtotal_snapshot' => 'decimal:2',
    ];

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class, 'venda_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }
}

