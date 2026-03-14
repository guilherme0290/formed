<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Papel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientesFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_filter_finds_cliente_from_autocomplete_suggestion_with_special_characters(): void
    {
        [$empresa, $user] = $this->createAuthenticatedMasterUser();

        $clienteEncontrado = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_pessoa' => 'PJ',
            'razao_social' => 'K2M3 Tecnologia, Comércio e Serviços Ltda',
            'nome_fantasia' => 'K2M3',
            'cnpj' => '12.345.678/0001-99',
            'email' => 'contato@k2m3.com.br',
            'telefone' => '(67) 99999-1234',
            'ativo' => true,
        ]);

        Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_pessoa' => 'PJ',
            'razao_social' => 'Outro Cliente Industrial Ltda',
            'nome_fantasia' => 'Outro Cliente',
            'cnpj' => '98.765.432/0001-10',
            'email' => 'contato@outro.com.br',
            'telefone' => '(67) 98888-0000',
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('clientes.index', [
                'texto' => 'K2M3 TECNOLOGIA, COMERCIO E SERVICOS LTDA',
                'status' => 'todos',
            ]));

        $clientes = $response->viewData('clientes');

        $response->assertOk();
        $this->assertCount(1, $clientes);
        $this->assertSame($clienteEncontrado->id, $clientes->first()->id);
    }

    public function test_master_filter_finds_cliente_by_masked_document(): void
    {
        [$empresa, $user] = $this->createAuthenticatedMasterUser();

        $clienteEncontrado = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_pessoa' => 'PJ',
            'razao_social' => 'Documento Exato Ltda',
            'cnpj' => '55.444.333/0001-22',
            'ativo' => true,
        ]);

        Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_pessoa' => 'PJ',
            'razao_social' => 'Nao Deve Retornar Ltda',
            'cnpj' => '10.222.333/0001-44',
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('clientes.index', [
                'documento' => '55.444.333/0001-22',
                'status' => 'todos',
            ]));

        $clientes = $response->viewData('clientes');

        $response->assertOk();
        $this->assertCount(1, $clientes);
        $this->assertSame($clienteEncontrado->id, $clientes->first()->id);
    }

    public function test_master_filter_finds_pf_cliente_by_masked_cpf(): void
    {
        [$empresa, $user] = $this->createAuthenticatedMasterUser();

        $clienteEncontrado = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_pessoa' => 'PF',
            'razao_social' => 'Maria da Silva',
            'cpf' => '12345678909',
            'ativo' => true,
        ]);

        Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_pessoa' => 'PF',
            'razao_social' => 'Ana Souza',
            'cpf' => '98765432100',
            'ativo' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('clientes.index', [
                'documento' => '123.456.789-09',
                'status' => 'todos',
            ]));

        $clientes = $response->viewData('clientes');

        $response->assertOk();
        $this->assertCount(1, $clientes);
        $this->assertSame($clienteEncontrado->id, $clientes->first()->id);
    }

    private function createAuthenticatedMasterUser(): array
    {
        $empresa = Empresa::create([
            'nome' => 'Empresa Teste',
        ]);

        $papel = Papel::create([
            'nome' => 'Master',
        ]);

        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'papel_id' => $papel->id,
        ]);

        return [$empresa, $user];
    }
}
