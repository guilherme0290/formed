<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteContratoDocumento extends Model
{
    protected $table = 'cliente_contrato_documentos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'cliente_contrato_id',
        'status',
        'html',
        'html_original',
        'clausulas_snapshot',
        'gerado_por',
        'atualizado_por',
    ];

    protected $casts = [
        'clausulas_snapshot' => 'array',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(ClienteContrato::class, 'cliente_contrato_id');
    }
}
