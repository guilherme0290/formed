<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model {
    protected $fillable = ['empresa_id','razao_social','nome_fantasia','cnpj','email','telefone','endereco','ativo'];
    public function empresa(){ return $this->belongsTo(Empresa::class); }

    public function cidade(){ return $this->belongsTo(Cidade::class); }

}
