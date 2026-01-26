<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicoesTabPreco extends Model
{
    protected $table = 'medicoes_tab_preco';

    protected $fillable = [
        'empresa_id',
        'titulo',
        'descricao',
        'preco',
        'ativo',
    ];

    protected $casts = [
        'preco' => 'decimal:2',
        'ativo' => 'bool',
    ];
}
