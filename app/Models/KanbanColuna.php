<?php // app/Models/KanbanColuna.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class KanbanColuna extends Model {
    protected $fillable = ['empresa_id','nome','ordem','finaliza'];
    public function empresa(){ return $this->belongsTo(Empresa::class); }
    public function tarefas(){ return $this->hasMany(Tarefa::class, 'coluna_id')->orderBy('ordem'); }
}
