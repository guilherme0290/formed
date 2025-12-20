<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteContratoVigenciaItem extends Model
{
    protected $table = 'cliente_contrato_vigencia_itens';

    protected $fillable = [
        'vigencia_id',
        'servico_id',
        'descricao_snapshot',
        'preco_unitario_snapshot',
        'unidade_cobranca',
        'regras_snapshot',
    ];

    protected $casts = [
        'preco_unitario_snapshot' => 'decimal:2',
        'regras_snapshot' => 'array',
    ];

    public function vigencia(): BelongsTo
    {
        return $this->belongsTo(ClienteContratoVigencia::class, 'vigencia_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }
}
