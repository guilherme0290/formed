<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarefa extends Model
{
    use HasFactory;

    protected $table = 'tarefas';

    protected $fillable = [
        'empresa_id',
        'coluna_id',
        'responsavel_id',
        'cliente_id',      // 👈 IMPORTANTE
        'servico_id',      // (se já tiver essa coluna)
        'titulo',
        'descricao',
        'prioridade',
        'status',
        'inicio_previsto', // 👈 DATA + HORA DO EXAME
        'fim_previsto',
        'finalizado_em',
        'data_prevista',   // (se existir a coluna)
    ];

    protected $casts = [
        'inicio_previsto' => 'datetime',
        'fim_previsto'    => 'datetime',
        'finalizado_em'   => 'datetime',
        'data_prevista'   => 'date',
    ];

    // ===== RELACIONAMENTOS =====
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function coluna()
    {
        return $this->belongsTo(KanbanColuna::class, 'coluna_id');
    }
}
