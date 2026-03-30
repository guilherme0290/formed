<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Papel;
use App\Models\Proposta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropostasScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_listagem_padrao_nao_exibe_propostas_rapidas(): void
    {
        [$empresa, $user] = $this->createAuthenticatedMasterUser();
        $cliente = $this->createCliente($empresa, $user);

        $propostaPadrao = Proposta::create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'vendedor_id' => $user->id,
            'codigo' => 'PC-001',
            'tipo_modelo' => 'PADRAO',
            'forma_pagamento' => 'Boleto',
            'valor_bruto' => 1000,
            'desconto_percentual' => 0,
            'desconto_valor' => 0,
            'valor_total' => 1000,
            'status' => 'PENDENTE',
        ]);

        $propostaRapida = Proposta::create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'vendedor_id' => $user->id,
            'codigo' => 'PRR-001',
            'tipo_modelo' => 'RAPIDA',
            'forma_pagamento' => 'A combinar',
            'valor_bruto' => 500,
            'desconto_percentual' => 10,
            'desconto_valor' => 50,
            'valor_total' => 450,
            'status' => 'PENDENTE',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('comercial.propostas.index'));

        $propostas = $response->viewData('propostas');

        $response->assertOk();
        $this->assertTrue($propostas->contains(fn (Proposta $proposta) => $proposta->id === $propostaPadrao->id));
        $this->assertFalse($propostas->contains(fn (Proposta $proposta) => $proposta->id === $propostaRapida->id));
    }

    public function test_listagem_rapida_exibe_apenas_propostas_rapidas(): void
    {
        [$empresa, $user] = $this->createAuthenticatedMasterUser();
        $cliente = $this->createCliente($empresa, $user);

        $propostaPadrao = Proposta::create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'vendedor_id' => $user->id,
            'codigo' => 'PC-002',
            'tipo_modelo' => 'PADRAO',
            'forma_pagamento' => 'Boleto',
            'valor_bruto' => 1200,
            'desconto_percentual' => 0,
            'desconto_valor' => 0,
            'valor_total' => 1200,
            'status' => 'PENDENTE',
        ]);

        $propostaRapida = Proposta::create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'vendedor_id' => $user->id,
            'codigo' => 'PRR-002',
            'tipo_modelo' => 'RAPIDA',
            'forma_pagamento' => 'A combinar',
            'valor_bruto' => 700,
            'desconto_percentual' => 5,
            'desconto_valor' => 35,
            'valor_total' => 665,
            'status' => 'PENDENTE',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('comercial.propostas.rapidas.index'));

        $propostas = $response->viewData('propostas');

        $response->assertOk();
        $this->assertTrue($propostas->contains(fn (Proposta $proposta) => $proposta->id === $propostaRapida->id));
        $this->assertFalse($propostas->contains(fn (Proposta $proposta) => $proposta->id === $propostaPadrao->id));
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

    private function createCliente(Empresa $empresa, User $user): Cliente
    {
        return Cliente::create([
            'empresa_id' => $empresa->id,
            'vendedor_id' => $user->id,
            'tipo_pessoa' => 'PJ',
            'razao_social' => 'Cliente Teste Ltda',
            'cnpj' => '12.345.678/0001-99',
            'ativo' => true,
        ]);
    }
}
