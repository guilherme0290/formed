<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropostaContrato extends Model
{
    protected $table = 'proposta_contratos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'proposta_id',
        'status',
        'html',
        'html_original',
        'clausulas_snapshot',
        'prompt_custom',
        'gerado_por',
        'atualizado_por',
    ];

    protected $casts = [
        'clausulas_snapshot' => 'array',
    ];

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class, 'proposta_id');
    }
}
