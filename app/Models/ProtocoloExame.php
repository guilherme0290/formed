<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProtocoloExame extends Model
{
    protected $table = 'protocolos_exames';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(ProtocoloExameItem::class, 'protocolo_id');
    }

    public function exames(): BelongsToMany
    {
        return $this->belongsToMany(ExamesTabPreco::class, 'protocolo_exame_itens', 'protocolo_id', 'exame_id');
    }
}
