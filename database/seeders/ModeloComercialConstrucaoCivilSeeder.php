<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\ExamesTabPreco;
use App\Models\ModeloComercial;
use App\Models\ModeloComercialExame;
use App\Models\ModeloComercialItem;
use App\Models\ModeloComercialPreco;
use App\Models\ModeloComercialTabela;
use App\Models\ModeloComercialTabelaLinha;
use App\Models\ModeloComercialTreinamento;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\TreinamentoNrsTabPreco;
use Illuminate\Database\Seeder;

class ModeloComercialConstrucaoCivilSeeder extends Seeder
{
    public function run(): void
    {
        $empresas = Empresa::query()->get();

        foreach ($empresas as $empresa) {
            $empresaId = $empresa->id;

            $modelo = ModeloComercial::updateOrCreate(
                ['empresa_id' => $empresaId, 'segmento' => 'construcao-civil'],
                [
                    'titulo' => 'CONSTRUCAO CIVIL',
                    'intro_1' => 'Apresentamos uma proposta objetiva para canteiros de obra, com foco em regularidade legal, reducao de riscos e suporte continuo.',
                    'intro_2' => 'O modelo prioriza documentos essenciais, exames ocupacionais e treinamentos criticos, mantendo previsibilidade de custos.',
                    'beneficios' => 'Conformidade sem travar a obra, atendimento rapido para ASOs e documentacao sempre atualizada para fiscalizacoes.',
                    'rodape' => 'comercial@formed.com.br • (00) 0000-0000',
                    'usar_todos_exames' => false,
                    'esocial_descricao' => 'Envio de eventos de SST e registros obrigatorios. Faixas por quantidade de colaboradores.',
                    'ativo' => true,
                ]
            );

            $modelo->itens()->where('tipo', 'servico')->delete();
            $servicosEssenciais = [
                'PGR e inventario de riscos',
                'PCMSO e gestao de ASOs',
                'ASO admissional, periodico e demissional',
                'Treinamentos NRs aplicaveis ao canteiro',
                'LTCAT e suporte tecnico para fiscalizacao',
            ];

            foreach ($servicosEssenciais as $index => $descricao) {
                ModeloComercialItem::create([
                    'modelo_comercial_id' => $modelo->id,
                    'tipo' => 'servico',
                    'descricao' => $descricao,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);
            }

            $padrao = TabelaPrecoPadrao::firstOrCreate(
                ['empresa_id' => $empresaId, 'ativa' => true],
                ['nome' => 'Tabela Padr\u00e3o', 'ativa' => true]
            );

            $modelo->precos()->delete();
            $itensTabela = TabelaPrecoItem::query()
                ->with('servico')
                ->where('tabela_preco_padrao_id', $padrao->id)
                ->where('ativo', true)
                ->get();

            $servicosDesejados = [
                'PGR' => 1,
                'PCMSO' => 1,
                'LTCAT' => 1,
                'LTIP' => 1,
                'APR' => 1,
                'ART' => 1,
                'PAE' => 1,
            ];

            $ordem = 1;
            foreach ($servicosDesejados as $servicoNome => $quantidade) {
                $item = $itensTabela->first(function ($row) use ($servicoNome) {
                    return strtoupper((string) $row->servico?->nome) === strtoupper($servicoNome);
                });

                if (!$item) {
                    continue;
                }

                ModeloComercialPreco::create([
                    'modelo_comercial_id' => $modelo->id,
                    'tabela_preco_item_id' => $item->id,
                    'quantidade' => $quantidade,
                    'ordem' => $ordem++,
                    'ativo' => true,
                ]);
            }

            $modelo->exames()->delete();
            $exames = ExamesTabPreco::query()
                ->where('empresa_id', $empresaId)
                ->where('ativo', true)
                ->orderBy('titulo')
                ->take(6)
                ->get();

            foreach ($exames as $index => $exame) {
                ModeloComercialExame::create([
                    'modelo_comercial_id' => $modelo->id,
                    'exame_tab_preco_id' => $exame->id,
                    'quantidade' => 1,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);
            }

            $modelo->treinamentos()->delete();
            $treinamentos = TreinamentoNrsTabPreco::query()
                ->where('empresa_id', $empresaId)
                ->where('ativo', true)
                ->orderBy('ordem')
                ->get();

            $codigosDesejados = ['NR-35', 'NR-10', 'NR-33', 'NR-18', 'NR-12', 'NR-06'];
            $treinamentosSelecionados = $treinamentos->filter(function ($row) use ($codigosDesejados) {
                return in_array(strtoupper((string) $row->codigo), $codigosDesejados, true);
            })->values();

            foreach ($treinamentosSelecionados as $index => $treinamento) {
                ModeloComercialTreinamento::create([
                    'modelo_comercial_id' => $modelo->id,
                    'treinamento_nr_tab_preco_id' => $treinamento->id,
                    'quantidade' => 1,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);
            }

            $modelo->tabelas()->delete();

            $tabelaProgramas = ModeloComercialTabela::create([
                'modelo_comercial_id' => $modelo->id,
                'titulo' => 'Programas essenciais',
                'subtitulo' => 'Itens obrigatorios com foco em compliance no canteiro.',
                'colunas' => ['Programa', 'Validade', 'Observacoes'],
                'ordem' => 1,
                'ativo' => true,
            ]);

            $programasRows = [
                ['PCMSO', '12 meses', 'Controle medico ocupacional'],
                ['PGR', '24 meses', 'Inventario de riscos e plano de acao'],
                ['LTCAT', 'Sob demanda', 'Laudo tecnico para enquadramentos'],
            ];

            foreach ($programasRows as $index => $values) {
                ModeloComercialTabelaLinha::create([
                    'modelo_comercial_tabela_id' => $tabelaProgramas->id,
                    'valores' => $values,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);
            }

            $tabelaAso = ModeloComercialTabela::create([
                'modelo_comercial_id' => $modelo->id,
                'titulo' => 'ASO e exames base',
                'subtitulo' => 'Pacote sugerido para equipes operacionais.',
                'colunas' => ['Item', 'Inclui', 'Observacoes'],
                'ordem' => 2,
                'ativo' => true,
            ]);

            $asoRows = [
                ['ASO Admissional', 'Clinico + basicos', 'Validade 12 meses'],
                ['ASO Periodico', 'Clinico + complementares', 'Conforme risco'],
                ['ASO Demissional', 'Clinico', 'Agendamento em 24h'],
            ];

            foreach ($asoRows as $index => $values) {
                ModeloComercialTabelaLinha::create([
                    'modelo_comercial_tabela_id' => $tabelaAso->id,
                    'valores' => $values,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);
            }

            $tabelaTreinamentos = ModeloComercialTabela::create([
                'modelo_comercial_id' => $modelo->id,
                'titulo' => 'Treinamentos criticos',
                'subtitulo' => 'Prioridade para atividades em altura, eletricidade e espaco confinado.',
                'colunas' => ['NR', 'Tema', 'Validade'],
                'ordem' => 3,
                'ativo' => true,
            ]);

            $treinamentosRows = [
                ['NR-35', 'Trabalho em altura', '24 meses'],
                ['NR-10', 'Seguranca em eletricidade', '24 meses'],
                ['NR-33', 'Espaco confinado', '12 meses'],
            ];

            foreach ($treinamentosRows as $index => $values) {
                ModeloComercialTabelaLinha::create([
                    'modelo_comercial_tabela_id' => $tabelaTreinamentos->id,
                    'valores' => $values,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);
            }

            $tabelaFluxo = ModeloComercialTabela::create([
                'modelo_comercial_id' => $modelo->id,
                'titulo' => 'Fluxo de atendimento',
                'subtitulo' => 'Como o atendimento funciona no dia a dia.',
                'colunas' => ['Etapa', 'Descricao'],
                'ordem' => 4,
                'ativo' => true,
            ]);

            $fluxoRows = [
                ['Diagnostico inicial', 'Revisao de documentos e mapeamento de riscos.'],
                ['Execucao mensal', 'ASOs, treinamentos e envio de eventos eSocial.'],
                ['Acompanhamento', 'Relatorios e suporte continuo ao gestor da obra.'],
            ];

            foreach ($fluxoRows as $index => $values) {
                ModeloComercialTabelaLinha::create([
                    'modelo_comercial_tabela_id' => $tabelaFluxo->id,
                    'valores' => $values,
                    'ordem' => $index + 1,
                    'ativo' => true,
                ]);
            }
        }
    }
}
