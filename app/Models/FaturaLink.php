<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaturaLink extends Model
{
    protected $fillable = ['conta_receber_id', 'token', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function contaReceber(): BelongsTo
    {
        return $this->belongsTo(ContaReceber::class);
    }
}
