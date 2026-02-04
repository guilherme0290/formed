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
        'ghe_id',
        'nome',
        'protocolo_id',
        'protocolo_admissional_id',
        'protocolo_periodico_id',
        'protocolo_demissional_id',
        'protocolo_mudanca_funcao_id',
        'protocolo_retorno_trabalho_id',
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

    public function ghe(): BelongsTo
    {
        return $this->belongsTo(Ghe::class, 'ghe_id');
    }

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'protocolo_id');
    }

    public function protocoloAdmissional(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'protocolo_admissional_id');
    }

    public function protocoloPeriodico(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'protocolo_periodico_id');
    }

    public function protocoloDemissional(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'protocolo_demissional_id');
    }

    public function protocoloMudancaFuncao(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'protocolo_mudanca_funcao_id');
    }

    public function protocoloRetornoTrabalho(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'protocolo_retorno_trabalho_id');
    }

    public function funcoes(): HasMany
    {
        return $this->hasMany(ClienteGheFuncao::class, 'cliente_ghe_id');
    }

    public function asoGrupos(): HasMany
    {
        return $this->hasMany(ClienteAsoGrupo::class, 'cliente_ghe_id');
    }
}
