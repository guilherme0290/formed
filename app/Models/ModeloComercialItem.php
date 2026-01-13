<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeloComercialItem extends Model
{
    protected $table = 'modelo_comercial_itens';

    protected $fillable = [
        'modelo_comercial_id',
        'tipo',
        'descricao',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'bool',
    ];

    public function modelo()
    {
        return $this->belongsTo(ModeloComercial::class, 'modelo_comercial_id');
    }
}
