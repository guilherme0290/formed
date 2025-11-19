<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarefa extends Model
{
    use HasFactory;

    protected $table = 'tarefas';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'servico_id',
        'responsavel_id',
        'coluna_id',
        'titulo',
        'descricao',
        'prioridade',
        'data_prevista',
        'hora_prevista',
        'concluida_em',
        'status',
    ];

    protected $casts = [
        'data_prevista' => 'date',
        'concluida_em'  => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function coluna(): BelongsTo
    {
        return $this->belongsTo(KanbanColuna::class, 'coluna_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TarefaLog::class);
    }
}
