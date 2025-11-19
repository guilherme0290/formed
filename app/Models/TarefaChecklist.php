<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarefaChecklist extends Model
{
    protected $table = 'tarefa_checklists';
    protected $fillable = ['tarefa_id','titulo','feito'];

    public function tarefa() { return $this->belongsTo(Tarefa::class); }
}
