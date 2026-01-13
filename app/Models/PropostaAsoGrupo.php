<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropostaAsoGrupo extends Model
{
    protected $table = 'proposta_aso_grupos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'proposta_id',
        'tipo_aso',
        'grupo_exames_id',
        'total_exames',
    ];

    protected $casts = [
        'total_exames' => 'decimal:2',
    ];

    public function proposta(): BelongsTo
    {
        return $this->belongsTo(Proposta::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'grupo_exames_id');
    }
}
