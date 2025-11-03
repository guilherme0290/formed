<?php // app/Models/TarefaLog.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TarefaLog extends Model {
    protected $fillable = ['tarefa_id','usuario_id','coluna_origem_id','coluna_destino_id','acao','meta'];
    protected $casts = ['meta'=>'array'];
    public function tarefa(){ return $this->belongsTo(Tarefa::class); }
    public function usuario(){ return $this->belongsTo(Usuario::class); }
}

