<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteContrato extends Model
{
    protected $table = 'cliente_contratos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'vendedor_id',
        'proposta_id_origem',
        'status',
        'vigencia_inicio',
        'vigencia_fim',
        'created_by',
    ];

    protected $casts = [
        'vigencia_inicio' => 'date',
        'vigencia_fim' => 'date',
    ];

    protected $appends = ['valor_mensal'];

    public function itens(): HasMany
    {
        return $this->hasMany(ClienteContratoItem::class, 'cliente_contrato_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function propostaOrigem(): BelongsTo
    {
        return $this->belongsTo(Proposta::class, 'proposta_id_origem');
    }

    public function vigencias(): HasMany
    {
        return $this->hasMany(ClienteContratoVigencia::class, 'cliente_contrato_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ClienteContratoLog::class, 'cliente_contrato_id');
    }

    public function getValorMensalAttribute(): float
    {
        if (array_key_exists('valor_mensal', $this->attributes)) {
            return (float) $this->attributes['valor_mensal'];
        }

        return (float) $this->itens()
            ->where('ativo', true)
            ->sum('preco_unitario_snapshot');
    }
}
