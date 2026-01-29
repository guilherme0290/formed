<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicoTempo extends Model
{
    protected $table = 'servico_tempos';

    protected $fillable = [
        'empresa_id',
        'servico_id',
        'tempo_minutos',
        'ativo',
    ];

    protected $casts = [
        'tempo_minutos' => 'int',
        'ativo' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }
}
