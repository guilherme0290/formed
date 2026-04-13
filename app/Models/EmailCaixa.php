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
        'imap_host',
        'imap_porta',
        'imap_criptografia',
        'imap_usuario',
        'imap_senha',
        'imap_sent_folder',
        'ativo',
        'created_by',
    ];

    protected $casts = [
        'porta' => 'int',
        'timeout' => 'int',
        'imap_porta' => 'int',
        'requer_autenticacao' => 'bool',
        'ativo' => 'bool',
        'senha' => 'encrypted',
        'imap_senha' => 'encrypted',
    ];
}
