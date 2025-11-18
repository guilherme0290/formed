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

    public function historicos()
    {
        return $this->hasMany(TabelaPrecoItemHistorico::class, 'item_id');
    }

    // aceita "R$ 1.234,56" ou "1.234,56" e converte para 1234.56
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
