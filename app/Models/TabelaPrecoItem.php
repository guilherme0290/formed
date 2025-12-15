<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabelaPrecoItem extends Model
{
    protected $table = 'tabela_preco_items';

    protected $fillable = [
        'servico_id',
        'codigo',
        'descricao',
        'preco',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'bool',
        'preco' => 'decimal:2',
    ];

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    public function tabelaPadrao()
    {
        return $this->belongsTo(\App\Models\TabelaPrecoPadrao::class, 'tabela_preco_padrao_id');
    }


    public function setPrecoAttribute($value): void
    {
        if (is_string($value)) {
            // tira R$, pontos e espaços
            $value = str_replace(['R$', ' ', '.'], '', $value);
            // troca vírgula por ponto
            $value = str_replace(',', '.', $value);
        }

        $this->attributes['preco'] = (float) $value;
    }
}
