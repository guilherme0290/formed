<?php

namespace App\Traits;

use App\Models\Papel;
use Illuminate\Support\Collection;

trait HasRoles
{
    public function papeis()
    {
        $papelId = (int) ($this->papel_id ?? 0);
        if ($papelId <= 0) {
            return Papel::query()->whereRaw('1 = 0');
        }

        return Papel::query()->whereKey($papelId);
    }

    public function hasRole(string $nome): bool
    {
        return $this->papel()->where('nome', $nome)->exists();
    }

    public function hasAnyRole(array $nomes): bool
    {
        $papel = $this->papel;
        if (!$papel) {
            return false;
        }

        $atual = mb_strtolower((string) $papel->nome);
        $nomesNormalizados = array_map(fn ($n) => mb_strtolower((string) $n), $nomes);
        return in_array($atual, $nomesNormalizados, true);
    }

    public function permissoes()
    {
        $papel = $this->papel;
        if (!$papel) {
            return collect();
        }

        return $papel->permissoes instanceof Collection
            ? $papel->permissoes
            : collect($papel->permissoes)->unique('id');
    }

    public function canAccess(string $chavePermissao): bool
    {
        $temPermissoesDiretas = method_exists($this, 'permissoesDiretas')
            ? $this->permissoesDiretas()->exists()
            : false;

        if ($temPermissoesDiretas) {
            return $this->permissoesDiretas()
                ->where('chave', $chavePermissao)
                ->exists();
        }

        return $this->permissoes()->contains('chave', $chavePermissao);
    }

    public function isMaster(): bool
    {
        return $this->hasRole('Master') || $this->email === 'admin@formed.com.br';
    }
}
