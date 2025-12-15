<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabelaPrecoPadrao extends Model
{
    protected $table = 'tabela_precos_padrao';

    protected $fillable = [
        'empresa_id','nome','ativa',
    ];

    protected $casts = [
        'ativa' => 'bool',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function itens()
    {
        return $this->hasMany(TabelaPrecoItem::class, 'tabela_preco_padrao_id');
    }
}
