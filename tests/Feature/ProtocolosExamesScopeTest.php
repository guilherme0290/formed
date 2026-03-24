<?php

namespace Tests\Feature;

use App\Http\Controllers\Comercial\ProtocolosExamesController;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\ExamesTabPreco;
use App\Models\ProtocoloExame;
use App\Models\ProtocoloExameItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtocolosExamesScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_cliente_scope_lists_generic_and_same_client_groups_only(): void
    {
        [$user, $empresa] = $this->makeUserAndEmpresa();
        $clienteA = Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'Cliente A',
        ]);
        $clienteB = Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'Cliente B',
        ]);

        $generico = ProtocoloExame::create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Genérico',
            'ativo' => true,
        ]);

        $exclusivoA = ProtocoloExame::create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $clienteA->id,
            'titulo' => 'Exclusivo A',
            'ativo' => true,
        ]);

        ProtocoloExame::create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $clienteB->id,
            'titulo' => 'Exclusivo B',
            'ativo' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('comercial.protocolos-exames.indexJson', ['cliente_id' => $clienteA->id]));

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $generico->id, 'escopo' => 'generico'])
            ->assertJsonFragment(['id' => $exclusivoA->id, 'escopo' => 'cliente'])
            ->assertJsonMissing(['titulo' => 'Exclusivo B']);
    }

    public function test_store_rejects_duplicate_client_group_when_generic_combo_already_exists(): void
    {
        [$user, $empresa] = $this->makeUserAndEmpresa();
        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'Cliente A',
        ]);
        [$exameA, $exameB] = $this->makeExames($empresa->id);

        $existente = ProtocoloExame::create([
            'empresa_id' => $empresa->id,
            'titulo' => 'ASO Base',
            'ativo' => true,
        ]);

        ProtocoloExameItem::insert([
            ['protocolo_id' => $existente->id, 'exame_id' => $exameA->id],
            ['protocolo_id' => $existente->id, 'exame_id' => $exameB->id],
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('comercial.protocolos-exames.store'), [
                'cliente_id' => $cliente->id,
                'titulo' => 'ASO Cliente A',
                'ativo' => true,
                'exames' => [$exameB->id, $exameA->id],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['exames']);
    }

    public function test_store_allows_same_combo_for_different_client_specific_groups(): void
    {
        [$user, $empresa] = $this->makeUserAndEmpresa();
        $clienteA = Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'Cliente A',
        ]);
        $clienteB = Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'Cliente B',
        ]);
        [$exameA, $exameB] = $this->makeExames($empresa->id);

        $existente = ProtocoloExame::create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $clienteA->id,
            'titulo' => 'ASO Cliente A',
            'ativo' => true,
        ]);

        ProtocoloExameItem::insert([
            ['protocolo_id' => $existente->id, 'exame_id' => $exameA->id],
            ['protocolo_id' => $existente->id, 'exame_id' => $exameB->id],
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('comercial.protocolos-exames.store'), [
                'cliente_id' => $clienteB->id,
                'titulo' => 'ASO Cliente B',
                'ativo' => true,
                'exames' => [$exameA->id, $exameB->id],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.cliente_id', $clienteB->id);
    }

    public function test_update_allows_legacy_duplicate_to_keep_same_exam_combination(): void
    {
        [$user, $empresa] = $this->makeUserAndEmpresa();
        [$exameA, $exameB] = $this->makeExames($empresa->id);

        $grupoA = ProtocoloExame::create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Grupo A',
            'ativo' => true,
        ]);

        $grupoB = ProtocoloExame::create([
            'empresa_id' => $empresa->id,
            'titulo' => 'Grupo B',
            'ativo' => true,
        ]);

        ProtocoloExameItem::insert([
            ['protocolo_id' => $grupoA->id, 'exame_id' => $exameA->id],
            ['protocolo_id' => $grupoA->id, 'exame_id' => $exameB->id],
            ['protocolo_id' => $grupoB->id, 'exame_id' => $exameA->id],
            ['protocolo_id' => $grupoB->id, 'exame_id' => $exameB->id],
        ]);

        $this->be($user);

        $request = Request::create('/comercial/protocolos-exames/' . $grupoB->id, 'PUT', [
            'titulo' => 'Grupo B Ajustado',
            'ativo' => true,
            'exames' => [$exameB->id, $exameA->id],
        ]);

        $response = app(ProtocolosExamesController::class)->update($request, $grupoB);
        $payload = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Grupo B Ajustado', $payload['data']['titulo']);
    }

    private function makeUserAndEmpresa(): array
    {
        $empresa = Empresa::create([
            'nome' => 'Empresa Teste',
            'ativo' => true,
        ]);

        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        return [$user, $empresa];
    }

    private function makeExames(int $empresaId): array
    {
        $exameA = ExamesTabPreco::create([
            'empresa_id' => $empresaId,
            'titulo' => 'Hemograma',
            'preco' => 10,
            'ativo' => true,
        ]);

        $exameB = ExamesTabPreco::create([
            'empresa_id' => $empresaId,
            'titulo' => 'Acuidade Visual',
            'preco' => 20,
            'ativo' => true,
        ]);

        return [$exameA, $exameB];
    }
}
