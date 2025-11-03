<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Servico extends Model {
    protected $fillable = ['empresa_id','nome','tipo','esocial','valor_base','ativo'];
    public function empresa(){ return $this->belongsTo(Empresa::class); }
}
