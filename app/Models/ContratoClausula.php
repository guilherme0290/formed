<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoClausula extends Model
{
    protected $table = 'contrato_clausulas';

    protected $fillable = [
        'empresa_id',
        'servico_tipo',
        'slug',
        'titulo',
        'ordem',
        'html_template',
        'ativo',
        'versao',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
        'versao' => 'integer',
    ];
}
