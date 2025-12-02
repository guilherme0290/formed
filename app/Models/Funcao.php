<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Funcao extends Model
{
    protected $table = 'funcoes';

    protected $fillable = [
        'empresa_id',
        'nome',
        'cbo',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    // Escopo para sempre filtrar pela empresa logada
    public function scopeDaEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function funcionarios()
    {
        return $this->hasMany(Funcionario::class);
    }
}
