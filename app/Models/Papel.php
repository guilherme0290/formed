<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Papel extends Model {
    protected $table = 'papeis';
    protected $fillable = ['nome','descricao','ativo'];
    public function permissoes(){ return $this->belongsToMany(Permissao::class,'papel_permissao'); }
    public function usuarios(){ return $this->belongsToMany(Usuario::class,'usuario_papel'); }
}
