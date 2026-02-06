<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametroClienteAsoGrupo extends Model
{
    protected $table = 'parametro_cliente_aso_grupos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'cliente_ghe_id',
        'parametro_cliente_id',
        'tipo_aso',
        'grupo_exames_id',
        'total_exames',
    ];

    protected $casts = [
        'total_exames' => 'decimal:2',
    ];

    public function parametro(): BelongsTo
    {
        return $this->belongsTo(ParametroCliente::class, 'parametro_cliente_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'grupo_exames_id');
    }

    public function clienteGhe(): BelongsTo
    {
        return $this->belongsTo(ClienteGhe::class, 'cliente_ghe_id');
    }
}
