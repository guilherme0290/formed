<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicoComissao extends Model
{
    protected $table = 'servico_comissoes';

    protected $fillable = [
        'empresa_id',
        'servico_id',
        'percentual',
        'vigencia_inicio',
        'vigencia_fim',
        'ativo',
        'created_by',
    ];

    protected $casts = [
        'vigencia_inicio' => 'date',
        'vigencia_fim' => 'date',
        'ativo' => 'boolean',
    ];

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }
}
