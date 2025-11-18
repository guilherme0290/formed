<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarefa extends Model
{
    protected $table = 'tarefas';
    protected $fillable = [
        'empresa_id','cliente_id','servico_id','coluna_id','responsavel_id',
        'titulo','descricao','prioridade','status'
    ];

    public function empresa()      { return $this->belongsTo(Empresa::class); }
    public function coluna()       { return $this->belongsTo(KanbanColuna::class,'coluna_id'); }
    public function responsavel()  { return $this->belongsTo(User::class,'responsavel_id'); }
    public function cliente()      { return $this->belongsTo(Cliente::class,'cliente_id'); }
    public function servico()      { return $this->belongsTo(Servico::class,'servico_id'); }

    public function checklists()   { return $this->hasMany(TarefaChecklist::class); }
    public function anexos()       { return $this->morphMany(Anexo::class, 'anexavel'); }
}
