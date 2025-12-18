<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteTabelaPreco extends Model
{
    protected $table = 'cliente_tabela_precos';

    protected $fillable = [
        'empresa_id','cliente_id','origem_proposta_id',
        'vigencia_inicio','vigencia_fim','ativa','observacoes'
    ];

    protected $casts = [
        'ativa' => 'bool',
        'vigencia_inicio' => 'datetime',
        'vigencia_fim' => 'datetime',
    ];

    public function cliente() { return $this->belongsTo(Cliente::class); }
    public function empresa() { return $this->belongsTo(Empresa::class); }
    public function propostaOrigem() { return $this->belongsTo(Proposta::class, 'origem_proposta_id'); }

    public function itens()
    {
        return $this->hasMany(ClienteTabelaPrecoItem::class, 'cliente_tabela_preco_id');
    }
}
