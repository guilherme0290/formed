<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TarefaLog extends Model
{
    use HasFactory;

    protected $table = 'tarefa_logs';



    protected $fillable = [
        'tarefa_id',
        'user_id',
        'de_coluna_id',
        'para_coluna_id',
        'acao',  // criado, movido, editado, finalizado
        'observacao',
    ];

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deColuna(): BelongsTo
    {
        return $this->belongsTo(KanbanColuna::class, 'de_coluna_id');
    }

    public function paraColuna(): BelongsTo
    {
        return $this->belongsTo(KanbanColuna::class, 'para_coluna_id');
    }
}
