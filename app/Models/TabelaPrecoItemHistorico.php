<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabelaPrecoItemHistorico extends Model
{
    protected $table = 'tabela_preco_item_historicos';
    protected $fillable = ['item_id','preco_anterior','preco_novo','user_id'];
    protected $casts = [
        'preco_anterior' => 'decimal:2',
        'preco_novo'     => 'decimal:2',
    ];

    public function item(){ return $this->belongsTo(TabelaPrecoItem::class, 'item_id'); }
    public function user(){ return $this->belongsTo(User::class); }
}
