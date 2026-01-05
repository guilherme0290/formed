<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaReceber extends Model
{
    protected $table = 'contas_receber';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'status',
        'total',
        'total_baixado',
        'vencimento',
        'pago_em',
        'boleto_status',
        'boleto_id',
        'boleto_url',
        'boleto_emitido_em',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'total_baixado' => 'decimal:2',
        'vencimento' => 'date',
        'pago_em' => 'date',
        'boleto_emitido_em' => 'datetime',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(ContaReceberItem::class, 'conta_receber_id');
    }

    public function baixas(): HasMany
    {
        return $this->hasMany(ContaReceberBaixa::class, 'conta_receber_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function getTotalAbertoAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->total_baixado);
    }
}
