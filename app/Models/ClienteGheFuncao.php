<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteGheFuncao extends Model
{
    protected $table = 'cliente_ghe_funcoes';

    protected $fillable = [
        'cliente_ghe_id',
        'funcao_id',
    ];

    public function ghe(): BelongsTo
    {
        return $this->belongsTo(ClienteGhe::class, 'cliente_ghe_id');
    }

    public function funcao(): BelongsTo
    {
        return $this->belongsTo(Funcao::class, 'funcao_id');
    }
}
