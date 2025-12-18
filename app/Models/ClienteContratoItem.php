<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteContratoItem extends Model
{
    protected $table = 'cliente_contrato_itens';

    protected $fillable = [
        'cliente_contrato_id',
        'servico_id',
        'descricao_snapshot',
        'preco_unitario_snapshot',
        'unidade_cobranca',
        'regras_snapshot',
        'ativo',
    ];

    protected $casts = [
        'preco_unitario_snapshot' => 'decimal:2',
        'regras_snapshot' => 'array',
        'ativo' => 'boolean',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(ClienteContrato::class, 'cliente_contrato_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }
}

