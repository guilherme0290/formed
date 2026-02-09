<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametroClienteItem extends Model
{
    protected $table = 'parametro_cliente_itens';

    protected $fillable = [
        'parametro_cliente_id',
        'servico_id',
        'tipo',
        'nome',
        'descricao',
        'valor_unitario',
        'acrescimo',
        'desconto',
        'quantidade',
        'prazo',
        'valor_total',
        'meta',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
        'acrescimo' => 'decimal:2',
        'desconto' => 'decimal:2',
        'valor_total' => 'decimal:2',
        'meta' => 'array',
    ];

    public function parametro(): BelongsTo
    {
        return $this->belongsTo(ParametroCliente::class, 'parametro_cliente_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }
}
