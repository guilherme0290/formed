<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeloComercialExame extends Model
{
    protected $table = 'modelo_comercial_exames';

    protected $fillable = [
        'modelo_comercial_id',
        'exame_tab_preco_id',
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

    public function exame()
    {
        return $this->belongsTo(ExamesTabPreco::class, 'exame_tab_preco_id');
    }
}
