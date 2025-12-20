<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteContratoVigencia extends Model
{
    protected $table = 'cliente_contrato_vigencias';

    protected $fillable = [
        'cliente_contrato_id',
        'vigencia_inicio',
        'vigencia_fim',
        'criado_por',
        'observacao',
    ];

    protected $casts = [
        'vigencia_inicio' => 'date',
        'vigencia_fim' => 'date',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(ClienteContrato::class, 'cliente_contrato_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ClienteContratoVigenciaItem::class, 'vigencia_id');
    }
}
