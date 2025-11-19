<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KanbanColuna extends Model
{
    use HasFactory;

    protected $table = 'kanban_colunas';

    protected $fillable = [
        'empresa_id',
        'nome',
        'slug',
        'cor',
        'ordem',
        'finaliza',
        'atraso',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class, 'coluna_id');
    }
}
