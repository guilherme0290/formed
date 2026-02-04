<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ghe extends Model
{
    protected $table = 'ghes';

    protected $fillable = [
        'empresa_id',
        'nome',
        'grupo_exames_id',
        'base_aso_admissional',
        'base_aso_periodico',
        'base_aso_demissional',
        'base_aso_mudanca_funcao',
        'base_aso_retorno_trabalho',
        'preco_fechado_admissional',
        'preco_fechado_periodico',
        'preco_fechado_demissional',
        'preco_fechado_mudanca_funcao',
        'preco_fechado_retorno_trabalho',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'base_aso_admissional' => 'decimal:2',
        'base_aso_periodico' => 'decimal:2',
        'base_aso_demissional' => 'decimal:2',
        'base_aso_mudanca_funcao' => 'decimal:2',
        'base_aso_retorno_trabalho' => 'decimal:2',
        'preco_fechado_admissional' => 'decimal:2',
        'preco_fechado_periodico' => 'decimal:2',
        'preco_fechado_demissional' => 'decimal:2',
        'preco_fechado_mudanca_funcao' => 'decimal:2',
        'preco_fechado_retorno_trabalho' => 'decimal:2',
    ];

    public function grupoExames(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'grupo_exames_id');
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(ClienteGhe::class, 'ghe_id');
    }

    public function funcoes(): HasMany
    {
        return $this->hasMany(GheFuncao::class, 'ghe_id');
    }
}
