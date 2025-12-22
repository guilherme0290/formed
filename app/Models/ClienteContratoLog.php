<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteContratoLog extends Model
{
    protected $table = 'cliente_contrato_logs';

    protected $fillable = [
        'cliente_contrato_id',
        'user_id',
        'servico_id',
        'acao',
        'motivo',
        'descricao',
        'valor_anterior',
        'valor_novo',
    ];

    protected $casts = [
        'valor_anterior' => 'decimal:2',
        'valor_novo' => 'decimal:2',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(ClienteContrato::class, 'cliente_contrato_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }
}
