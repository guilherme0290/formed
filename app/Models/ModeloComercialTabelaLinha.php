<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModeloComercialTabelaLinha extends Model
{
    protected $table = 'modelo_comercial_tabela_linhas';

    protected $fillable = [
        'modelo_comercial_tabela_id',
        'valores',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'valores' => 'array',
        'ativo' => 'bool',
    ];

    public function tabela(): BelongsTo
    {
        return $this->belongsTo(ModeloComercialTabela::class, 'modelo_comercial_tabela_id');
    }
}
