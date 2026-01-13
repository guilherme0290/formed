<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeloComercialTreinamento extends Model
{
    protected $table = 'modelo_comercial_treinamentos';

    protected $fillable = [
        'modelo_comercial_id',
        'treinamento_nr_tab_preco_id',
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

    public function treinamento()
    {
        return $this->belongsTo(TreinamentoNrsTabPreco::class, 'treinamento_nr_tab_preco_id');
    }
}
