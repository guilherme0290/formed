<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaTarefa extends Model
{
    protected $table = 'agenda_tarefas';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'titulo',
        'descricao',
        'tipo',
        'prioridade',
        'data',
        'hora',
        'cliente',
        'status',
        'concluida_em',
    ];

    protected $casts = [
        'data' => 'date',
        'hora' => 'datetime:H:i',
        'concluida_em' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
