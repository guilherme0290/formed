<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropostaItens extends Model
{
    protected $fillable = [
        'proposta_id','servico_id','tipo','nome','descricao',
        'valor_unitario','acrescimo','desconto',
        'quantidade','prazo','valor_total','meta',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
        'acrescimo'      => 'decimal:2',
        'desconto'       => 'decimal:2',
        'valor_total'    => 'decimal:2',
        'meta'           => 'array',
    ];


    public function proposta()
    {
        return $this->belongsTo(Proposta::class);
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }
}
