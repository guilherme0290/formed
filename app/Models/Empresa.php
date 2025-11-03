<?php // app/Models/Empresa.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model {
    protected $fillable = ['razao_social','nome_fantasia','cnpj','email','telefone','endereco','ativo'];
    public function usuarios(){ return $this->hasMany(Usuario::class); }

    public function cidade(){ return $this->belongsTo(Cidade::class); }
}
