<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreinamentoNrsTabPreco extends Model
{
    protected $table = 'treinamento_nrs_tab_preco';

    protected $fillable = [
        'codigo',
        'titulo',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ordem' => 'int',
        'ativo' => 'bool',
    ];
}
