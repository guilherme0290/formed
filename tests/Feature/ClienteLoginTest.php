<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Papel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_can_login_with_cpf_documento(): void
    {
        $empresa = Empresa::create([
            'nome' => 'Empresa Teste',
        ]);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_pessoa' => 'PF',
            'razao_social' => 'Maria da Silva',
            'cpf' => '12345678909',
            'ativo' => true,
        ]);

        $papelCliente = Papel::create([
            'nome' => 'Cliente',
        ]);

        User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel_id' => $papelCliente->id,
            'cliente_id' => $cliente->id,
            'documento' => '12345678909',
            'password' => 'SenhaTeste123!',
            'must_change_password' => false,
        ]);

        $response = $this->post(route('login'), [
            'login' => '123.456.789-09',
            'password' => 'SenhaTeste123!',
            'redirect' => 'cliente',
        ]);

        $response->assertRedirect(route('cliente.dashboard'));
        $this->assertAuthenticated();
    }
}
