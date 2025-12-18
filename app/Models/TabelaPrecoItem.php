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
        'tabela_preco_padrao_id'
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
        if ($value === null || $value === '') {
            $this->attributes['preco'] = 0;
            return;
        }

        if (is_numeric($value)) {
            $this->attributes['preco'] = (float) $value;
            return;
        }

        $s = trim((string) $value);
        $s = str_replace(['R$', ' '], '', $s);

        $temVirgula = str_contains($s, ',');
        $temPonto   = str_contains($s, '.');

        if ($temVirgula) {
            // formato BR: 1.234,56  -> remove milhares '.' e troca ',' por '.'
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            // formato US/normal: 1234.56 -> mantém o ponto decimal
            // (se vier 1.234.567.89 errado, você pode tratar depois)
            $s = preg_replace('/[^0-9.]/', '', $s);
        }

        $this->attributes['preco'] = (float) $s;
    }
}
