<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContaReceberItem extends Model
{
    protected $table = 'contas_receber_itens';

    protected $fillable = [
        'conta_receber_id',
        'empresa_id',
        'cliente_id',
        'venda_id',
        'venda_item_id',
        'servico_id',
        'descricao',
        'data_realizacao',
        'vencimento',
        'status',
        'valor',
        'baixado_em',
    ];

    protected $casts = [
        'data_realizacao' => 'date',
        'vencimento' => 'date',
        'valor' => 'decimal:2',
        'baixado_em' => 'datetime',
    ];

    public function contaReceber(): BelongsTo
    {
        return $this->belongsTo(ContaReceber::class, 'conta_receber_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class);
    }

    public function vendaItem(): BelongsTo
    {
        return $this->belongsTo(VendaItem::class, 'venda_item_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function baixas(): HasMany
    {
        return $this->hasMany(ContaReceberBaixa::class, 'conta_receber_item_id');
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
