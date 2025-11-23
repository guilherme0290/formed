<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreinamentoNrDetalhes extends Model
{
    protected $table = 'treinamento_nr_detalhes';

    protected $fillable = [
        'tarefa_id',
        'local_tipo',
        'unidade_id',
    ];

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function unidade(): BelongsTo
    {
        return $this->belongsTo(UnidadeClinica::class);
    }
}
