<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GheFuncao extends Model
{
    protected $table = 'ghe_funcoes';

    protected $fillable = [
        'ghe_id',
        'funcao_id',
    ];

    public function ghe(): BelongsTo
    {
        return $this->belongsTo(Ghe::class, 'ghe_id');
    }

    public function funcao(): BelongsTo
    {
        return $this->belongsTo(Funcao::class, 'funcao_id');
    }
}
