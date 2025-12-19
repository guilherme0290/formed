<?php

namespace Database\Seeders;

use App\Models\Permissao;
use App\Models\Papel;
use Illuminate\Database\Seeder;

class PermissoesSeeder extends Seeder
{
    public function run(): void
    {
        $map = config('permissions', []);

        foreach ($map as $escopo => $permissoes) {
            foreach ($permissoes as $chave => $nome) {
                Permissao::updateOrCreate(
                    ['chave' => $chave],
                    ['nome' => $nome, 'escopo' => $escopo]
                );
            }
        }

        // atribuição padrão para manter comportamento esperado
        $this->atribuirPermissoes('Master', array_keys($map['master'] ?? []));
        $this->atribuirPermissoes('Comercial', array_keys($map['comercial'] ?? []));
        $this->atribuirPermissoes('Operacional', array_keys($map['operacional'] ?? []));
        $this->atribuirPermissoes('Cliente', array_keys($map['cliente'] ?? []));
    }

    private function atribuirPermissoes(string $nomePapel, array $chaves): void
    {
        $papel = Papel::whereRaw('lower(nome) = ?', [mb_strtolower($nomePapel)])->first();
        if (!$papel) {
            return;
        }

        $ids = Permissao::whereIn('chave', $chaves)->pluck('id');
        $papel->permissoes()->sync($ids);
    }
}
