<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comissao extends Model
{
    protected $table = 'comissoes';

    protected $fillable = [
        'empresa_id',
        'venda_id',
        'venda_item_id',
        'vendedor_id',
        'cliente_id',
        'servico_id',
        'valor_base',
        'percentual',
        'valor_comissao',
        'status',
        'gerada_em',
    ];

    protected $casts = [
        'valor_base' => 'decimal:2',
        'percentual' => 'decimal:2',
        'valor_comissao' => 'decimal:2',
        'gerada_em' => 'datetime',
    ];
}
