<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Papel extends Model
{
    protected $table = 'papeis';

    protected $fillable = ['nome', 'descricao', 'ativo'];

    protected $casts = [
        'ativo' => 'bool',
    ];

    /** Permissões do papel (N:N) */
    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'papel_permissao');
    }

    /** Usuários que pertencem a este papel (1:N via users.papel_id) */
    public function users() { return $this->hasMany(\App\Models\User::class); }

}
