<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposta extends Model
{
    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'vendedor_id',
        'codigo',
        'tipo_modelo',
        'public_token',
        'forma_pagamento',
        'incluir_esocial',
        'esocial_qtd_funcionarios',
        'esocial_valor_mensal',
        'valor_bruto',
        'desconto_percentual',
        'desconto_valor',
        'valor_total',
        'status',
        'prazo_dias',
        'public_responded_at',
        'pipeline_status',
        'pipeline_updated_at',
        'pipeline_updated_by',
        'perdido_motivo',
        'perdido_observacao',
        'vencimento_servicos',
        'observacoes',
    ];

    protected $casts = [
        'incluir_esocial'       => 'bool',
        'esocial_valor_mensal'  => 'decimal:2',
        'valor_bruto'           => 'decimal:2',
        'valor_total'           => 'decimal:2',
        'desconto_percentual'   => 'decimal:2',
        'desconto_valor'        => 'decimal:2',
        'prazo_dias'            => 'int',
        'pipeline_updated_at'   => 'datetime',
        'public_responded_at'   => 'datetime',
        'vencimento_servicos'   => 'int',
    ];

    public function scopePadrao($query)
    {
        return $query->where(function ($subQuery) {
            $subQuery->whereNull('tipo_modelo')
                ->orWhere('tipo_modelo', 'PADRAO');
        });
    }

    public function scopeRapida($query)
    {
        return $query->where('tipo_modelo', 'RAPIDA');
    }

    public function isRapida(): bool
    {
        return strtoupper((string) $this->tipo_modelo) === 'RAPIDA';
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function itens()
    {
        return $this->hasMany(PropostaItens::class);
    }

    public function asoGrupos()
    {
        return $this->hasMany(PropostaAsoGrupo::class);
    }


}
