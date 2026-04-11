<?php

namespace Tests\Feature;

use App\Models\Papel;
use App\Models\Permissao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtectedUserAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createMasterUser(bool $isProtected = false): User
    {
        $papel = Papel::query()->firstOrCreate(
            ['nome' => 'Master'],
            ['descricao' => 'Acesso total ao sistema', 'ativo' => true]
        );

        $permissao = Permissao::query()->firstOrCreate(
            ['chave' => 'master.acessos.manage'],
            ['nome' => 'Gerir acessos/usuários', 'escopo' => 'master']
        );

        $papel->permissoes()->syncWithoutDetaching([$permissao->id]);

        return User::factory()->create([
            'name' => $isProtected ? 'S' : fake()->name(),
            'email' => $isProtected ? 'suporte@formed.com.br' : fake()->unique()->safeEmail(),
            'papel_id' => $papel->id,
            'ativo' => true,
            'is_protected' => $isProtected,
        ]);
    }

    public function test_non_protected_user_cannot_update_protected_user(): void
    {
        $actor = $this->createMasterUser();
        $protected = $this->createMasterUser(true);

        $response = $this->actingAs($actor)->patch(route('master.usuarios.update', $protected), [
            'name' => 'Tentativa de alteração',
            'email' => $protected->email,
            'telefone' => '67999990000',
            'papel_id' => $protected->papel_id,
            'ativo' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('erro');
        $this->assertNotSame('Tentativa de alteração', $protected->fresh()->name);
    }

    public function test_non_protected_user_cannot_change_protected_user_password(): void
    {
        $actor = $this->createMasterUser();
        $protected = $this->createMasterUser(true);

        $response = $this->actingAs($actor)->post(route('master.usuarios.password', $protected), [
            'password' => 'NovaSenha@123',
            'password_confirmation' => 'NovaSenha@123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('err');
    }

    public function test_non_protected_user_cannot_sync_permissions_of_protected_user(): void
    {
        $actor = $this->createMasterUser();
        $protected = $this->createMasterUser(true);
        $perm = Permissao::query()->firstOrCreate(
            ['chave' => 'master.dashboard.view'],
            ['nome' => 'Acessar painel master', 'escopo' => 'master']
        );

        $response = $this->actingAs($actor)->post(route('master.usuarios.permissoes.sync', $protected), [
            'permissoes' => [$perm->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(0, $protected->permissoesDiretas()->count());
    }

    public function test_protected_user_can_update_self(): void
    {
        $protected = $this->createMasterUser(true);

        $response = $this->actingAs($protected)->patch(route('master.usuarios.update', $protected), [
            'name' => 'S atualizado',
            'email' => $protected->email,
            'telefone' => '67999990000',
            'papel_id' => $protected->papel_id,
            'ativo' => '1',
        ]);

        $response->assertRedirect(route('master.acessos', ['tab' => 'usuarios']));
        $this->assertSame('S atualizado', $protected->fresh()->name);
    }
}
