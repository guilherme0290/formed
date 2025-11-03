<?php // app/Models/Tarefa.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Tarefa extends Model {
    protected $fillable = [
        'empresa_id','cliente_id','servico_id','coluna_id','responsavel_id',
        'titulo','descricao','prioridade','sla_horas','prazo','ordem','status'
    ];
    protected $casts = ['prazo'=>'datetime'];

    public function empresa(){ return $this->belongsTo(Empresa::class); }
    public function coluna(){ return $this->belongsTo(KanbanColuna::class, 'coluna_id'); }
    public function cliente(){ return $this->belongsTo(Cliente::class); }
    public function servico(){ return $this->belongsTo(Servico::class); }
    public function responsavel(){ return $this->belongsTo(Usuario::class, 'responsavel_id'); }
    public function logs(){ return $this->hasMany(TarefaLog::class); }
}
