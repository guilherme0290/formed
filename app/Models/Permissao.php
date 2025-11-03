<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permissao extends Model {
    protected $table = 'permissoes';
    protected $fillable = ['chave','nome','escopo'];
    public function papeis(){ return $this->belongsToMany(Papel::class,'papel_permissao'); }
}
