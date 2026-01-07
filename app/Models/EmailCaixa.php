<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailCaixa extends Model
{
    protected $fillable = [
        'empresa_id',
        'nome',
        'host',
        'porta',
        'criptografia',
        'timeout',
        'requer_autenticacao',
        'usuario',
        'senha',
        'ativo',
        'created_by',
    ];

    protected $casts = [
        'porta' => 'int',
        'timeout' => 'int',
        'requer_autenticacao' => 'bool',
        'ativo' => 'bool',
        'senha' => 'encrypted',
    ];
}
