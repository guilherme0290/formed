<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EsocialTabPreco extends Model
{
    protected $table = 'esocial_faixas_tab_preco';

    protected $fillable = [
        'empresa_id',
        'tabela_preco_padrao_id',
        'inicio',
        'fim',
        'descricao',
        'preco',
        'ativo',
    ];

    protected $casts = [
        'empresa_id'           => 'int',
        'tabela_preco_padrao_id'=> 'int',
        'inicio'               => 'int',
        'fim'                  => 'int',
        'preco'                => 'decimal:2',
        'ativo'                => 'bool',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tabelaPadrao()
    {
        return $this->belongsTo(TabelaPrecoPadrao::class, 'tabela_preco_padrao_id');
    }
}
