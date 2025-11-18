<?php // app/Models/TarefaLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarefaLog extends Model {
    protected $fillable = ['tarefa_id','user_id','acao','dados'];
    protected $casts = ['dados'=>'array'];

    public function tarefa(){ return $this->belongsTo(Tarefa::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
