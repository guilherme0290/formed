<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApresentacaoComercial extends Model
{
    protected $table = 'apresentacoes_comerciais';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'proposta_id',
        'cliente_id',
        'segmento',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proposta()
    {
        return $this->belongsTo(Proposta::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
