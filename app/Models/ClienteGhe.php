<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteGhe extends Model
{
    protected $table = 'cliente_ghes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'nome',
        'protocolo_id',
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
        'ativo' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'protocolo_id');
    }

    public function funcoes(): HasMany
    {
        return $this->hasMany(ClienteGheFuncao::class, 'cliente_ghe_id');
    }
}
