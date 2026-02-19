<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaPagar extends Model
{
    protected $table = 'contas_pagar';

    protected $fillable = [
        'empresa_id',
        'fornecedor_id',
        'status',
        'total',
        'total_baixado',
        'vencimento',
        'pago_em',
        'observacao',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'total_baixado' => 'decimal:2',
        'vencimento' => 'date',
        'pago_em' => 'date',
    ];

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ContaPagarItem::class, 'conta_pagar_id');
    }

    public function baixas(): HasMany
    {
        return $this->hasMany(ContaPagarBaixa::class, 'conta_pagar_id');
    }

    public function getTotalAbertoAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->total_baixado);
    }
}
