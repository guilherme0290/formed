<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteTabelaPrecoItem extends Model
{
    protected $table = 'cliente_tabela_preco_itens';

    protected $fillable = [
        'cliente_tabela_preco_id',
        'servico_id','tipo','codigo','nome','descricao',
        'valor_unitario','meta','ativo',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
        'meta' => 'array',
        'ativo' => 'bool',
    ];

    public function tabela()
    {
        return $this->belongsTo(ClienteTabelaPreco::class, 'cliente_tabela_preco_id');
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }
}
