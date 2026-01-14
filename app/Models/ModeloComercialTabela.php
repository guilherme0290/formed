<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeloComercialTabela extends Model
{
    protected $table = 'modelo_comercial_tabelas';

    protected $fillable = [
        'modelo_comercial_id',
        'titulo',
        'subtitulo',
        'colunas',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'colunas' => 'array',
        'ativo' => 'bool',
    ];

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ModeloComercial::class, 'modelo_comercial_id');
    }

    public function linhas(): HasMany
    {
        return $this->hasMany(ModeloComercialTabelaLinha::class, 'modelo_comercial_tabela_id');
    }
}
