<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtocoloExameItem extends Model
{
    protected $table = 'protocolo_exame_itens';

    protected $fillable = [
        'protocolo_id',
        'exame_id',
    ];

    public function protocolo(): BelongsTo
    {
        return $this->belongsTo(ProtocoloExame::class, 'protocolo_id');
    }

    public function exame(): BelongsTo
    {
        return $this->belongsTo(ExamesTabPreco::class, 'exame_id');
    }
}
