<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteUnidadePermitida extends Model
{
    protected $table = 'cliente_unidade_permitidas';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'unidade_id',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(UnidadeClinica::class, 'unidade_id');
    }
}
