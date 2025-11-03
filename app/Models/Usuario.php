<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable {
    use Notifiable;

    protected $table = 'usuarios';
    protected $fillable = ['empresa_id','nome','email','telefone','password','ativo','ultimo_acesso_at'];
    protected $hidden = ['password','remember_token'];

    public function empresa(){ return $this->belongsTo(Empresa::class); }
    public function papeis(){ return $this->belongsToMany(Papel::class,'usuario_papel'); }

    public function hasPermissao(string $chave): bool {
        return $this->papeis()->whereHas('permissoes', fn($q)=>$q->where('chave',$chave))->exists();
    }
}
