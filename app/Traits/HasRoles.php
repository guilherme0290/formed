<?php

namespace App\Traits;

use App\Models\Papel;
use App\Models\Permissao;

trait HasRoles
{
    public function papeis()
    {
        return $this->belongsToMany(Papel::class, 'usuario_papel', 'usuario_id', 'papel_id');
    }

    public function hasRole(string $nome): bool
    {
        return $this->papel()->where('nome', $nome)->exists();
    }

    public function hasAnyRole(array $nomes): bool
    {
        return $this->papeis()->whereIn('nome', $nomes)->exists();
    }

    public function permissoes()
    {
        return $this->papeis()->with('permissoes')->get()->pluck('permissoes')->flatten()->unique('id');
    }

    public function canAccess(string $chavePermissao): bool
    {
        return $this->permissoes()->contains('chave', $chavePermissao);
    }

    public function isMaster(): bool
    {
        return $this->hasRole('Master') || $this->email === 'admin@formed.com.br';
    }
}
