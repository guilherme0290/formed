<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaPagarItem extends Model
{
    protected $table = 'contas_pagar_itens';

    protected $fillable = [
        'conta_pagar_id',
        'empresa_id',
        'fornecedor_id',
        'categoria',
        'descricao',
        'data_competencia',
        'vencimento',
        'status',
        'valor',
        'baixado_em',
    ];

    protected $casts = [
        'data_competencia' => 'date',
        'vencimento' => 'date',
        'valor' => 'decimal:2',
        'baixado_em' => 'datetime',
    ];

    public function contaPagar(): BelongsTo
    {
        return $this->belongsTo(ContaPagar::class, 'conta_pagar_id');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
    }

    public function baixas(): HasMany
    {
        return $this->hasMany(ContaPagarBaixa::class, 'conta_pagar_item_id');
    }

    public function getTotalBaixadoAttribute(): float
    {
        return (float) $this->baixas()->sum('valor');
    }

    public function getVencidoAttribute(): bool
    {
        if (!$this->vencimento) {
            return false;
        }

        return $this->status === 'ABERTO' && $this->vencimento->lt(now()->startOfDay());
    }
}
