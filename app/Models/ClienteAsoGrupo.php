<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteAsoGrupo extends Model
{
    protected $table = 'cliente_aso_grupos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'cliente_ghe_id',
        'tipo_aso',
        'grupo_exames_id',
        'total_exames',
    ];

    protected $casts = [
        'total_exames' => 'decimal:2',
    ];

    public function clienteGhe(): BelongsTo
    {
        return $this->belongsTo(ClienteGhe::class, 'cliente_ghe_id');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'grupo_exames_id');
    }
}
