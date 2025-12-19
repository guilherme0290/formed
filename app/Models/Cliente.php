<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Cidade;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'empresa_id',
        'vendedor_id',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'email',
        'telefone',
        'cep',
        'endereco',
        'numero',
        'bairro',
        'complemento',
        'cidade_id',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function cidade()
    {
        return $this->belongsTo(Cidade::class);
    }

    public function userCliente()
    {
        return $this->hasOne(User::class, 'cliente_id');
    }
}
