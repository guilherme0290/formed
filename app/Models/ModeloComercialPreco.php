<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeloComercialPreco extends Model
{
    protected $table = 'modelo_comercial_precos';

    protected $fillable = [
        'modelo_comercial_id',
        'tabela_preco_item_id',
        'quantidade',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'bool',
        'quantidade' => 'decimal:2',
    ];

    public function modelo()
    {
        return $this->belongsTo(ModeloComercial::class, 'modelo_comercial_id');
    }

    public function tabelaPrecoItem()
    {
        return $this->belongsTo(TabelaPrecoItem::class, 'tabela_preco_item_id');
    }
}
