<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servico extends Model
{
    protected $fillable = ['nome', 'descricao', 'ativo','empresa_id'];

    protected $casts = [
        'ativo' => 'bool',
    ];
}
