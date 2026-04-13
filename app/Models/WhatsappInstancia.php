<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappInstancia extends Model
{
    public const TIPO_FINANCEIRO = 'financeiro';
    public const TIPO_OPERACIONAL = 'operacional';
    public const TIPOS = [
        self::TIPO_FINANCEIRO,
        self::TIPO_OPERACIONAL,
    ];

    protected $table = 'whatsapp_instancias';

    protected $fillable = [
        'empresa_id',
        'tipo',
        'provider',
        'base_url',
        'api_key',
        'channel',
        'token',
        'numero',
        'instance_name',
        'ativo',
        'last_state',
        'last_status_at',
        'last_error',
    ];

    protected $casts = [
        'ativo' => 'bool',
        'api_key' => 'encrypted',
        'token' => 'encrypted',
        'last_status_at' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function scopeDaEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    public function scopeDoTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            self::TIPO_FINANCEIRO => 'Financeiro',
            self::TIPO_OPERACIONAL => 'Operacional',
            default => ucfirst((string) $this->tipo),
        };
    }
}
