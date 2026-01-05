<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\EsocialTabPreco;
use App\Models\Servico;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\TreinamentoNrsTabPreco;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TabelaPrecoPadraoFullSeeder extends Seeder
{
    public function run(): void
    {
        // Se quiser rodar só para uma empresa específica:
        // $empresas = Empresa::where('id', 1)->get();
        $empresas = Empresa::query()->get();

        foreach ($empresas as $empresa) {
            $empresaId = $empresa->id;

            // 1) Garantir serviços básicos (se já existe, só reaproveita)
            $servicos = $this->ensureServicosBase($empresaId);

            // 2) Garantir tabela padrão ativa
            $padrao = TabelaPrecoPadrao::firstOrCreate(
                ['empresa_id' => $empresaId, 'ativa' => true],
                ['nome' => 'Tabela Padrão', 'ativa' => true]
            );

            // 3) Itens “normais” (ASO/PGR/PCMSO/LTCAT/LTIP/APR/PAE)
            $this->seedItensServicosBase($padrao->id, $servicos);

            // 4) Treinamentos NRs: cria itens na tabela_preco_items
            $this->seedItensTreinamentosNRs($empresaId, $padrao->id, $servicos['treinamentos']->id);

            // 5) eSocial: faixas na tabela esocial_faixas_tab_preco
            $this->seedFaixasEsocial($empresaId, $padrao->id);
        }
    }

    private function ensureServicosBase(int $empresaId): array
    {
        $map = [
            'ASO'             => 'Atestado de Saúde Ocupacional para colaboradores.',
            'PGR'             => 'Programa de Gerenciamento de Riscos.',
            'PCMSO'           => 'Programa de Controle Médico Ocupacional.',
            'LTCAT'           => 'Laudo Técnico das Condições Ambientais.',
            'LTIP'            => 'Laudo de Insalubridade e Periculosidade.',
            'APR'             => 'Análise Preliminar de Riscos.',
            'ART'             => 'Anotação de Responsabilidade Técnica.',
            'PAE'             => 'Plano de Atendimento a Emergências.',
            'Treinamentos NRs'=> 'Normas regulamentadoras e capacitações.',
            'Esocial'         => 'Esocial',
        ];

        $out = [];
        foreach ($map as $nome => $descricao) {
            $s = Servico::updateOrCreate(
                ['empresa_id' => $empresaId, 'nome' => $nome],
                [
                    'descricao'   => $descricao,
                    'ativo'       => true,
                ]
            );


            // atalhos pra acessar depois
            if ($nome === 'Treinamentos NRs') $out['treinamentos'] = $s;
            if ($nome === 'Esocial')          $out['esocial'] = $s;

            // também guarda os outros
            $out[Str::slug($nome, '_')] = $s;
        }

        return $out;
    }

    private function seedItensServicosBase(int $tabelaPadraoId, array $servicos): void
    {
        $itens = [
            // formato: [servicoKey, codigo, descricao, preco, ativo]
            ['pgr',   'PGR',      'PGR (Plano completo)',           450.00,  true],
            ['pcmso', 'PCMSO',    'PCMSO (Plano anual)',            390.00,  true],
            ['ltcat', 'LTCAT',    'LTCAT (Laudo técnico)',          800.00,  true],
            ['ltip',  'LTIP',     'LTIP (Insalubridade/Periculos.)',950.00,  true],
            ['apr',   'APR',      'APR (Análise preliminar)',       180.00,  true],
            ['art',   'ART',      'ART (Responsabilidade técnica)', 150.00,  true],
            ['pae',   'PAE',      'PAE (Plano de emergência)',      280.00,  true],

            // casos de teste
            ['pgr',   null,       'PGR (sem código p/ teste)',      420.00,  true],
            ['pcmso', 'PCMSO-0',  'PCMSO (preço zero p/ teste)',      0.00,  true],
        ];

        foreach ($itens as [$servKey, $codigo, $descricao, $preco, $ativo]) {
            $servico = $servicos[$servKey] ?? null;
            if (!$servico) continue;

            TabelaPrecoItem::updateOrCreate(
                [
                    'tabela_preco_padrao_id' => $tabelaPadraoId,
                    'servico_id'             => $servico->id,
                    'codigo'                 => $codigo,
                ],
                [
                    'descricao' => $descricao,
                    'preco'     => $preco,
                    'ativo'     => $ativo,
                ]
            );
        }
    }

    private function seedItensTreinamentosNRs(int $empresaId, int $tabelaPadraoId, int $servicoTreinamentoId): void
    {
        // Se você tem a tabela catálogo treinamento_nrs_preco, vamos puxar dela.
        // Se não tiver, cria uma lista fallback aqui.
        $catalogo = class_exists(TreinamentoNrsTabPreco::class)
            ? TreinamentoNrsTabPreco::where('empresa_id', $empresaId)->where('ativo', true)->orderBy('ordem')->get()
            : collect([
                (object)['codigo' => 'NR-35', 'titulo' => 'Trabalho em Altura', 'preco_sugerido' => 120],
                (object)['codigo' => 'NR-10', 'titulo' => 'Segurança em Eletricidade', 'preco_sugerido' => 220],
                (object)['codigo' => 'NR-33', 'titulo' => 'Espaço Confinado', 'preco_sugerido' => 150],
                (object)['codigo' => 'NR-12', 'titulo' => 'Máquinas e Equipamentos', 'preco_sugerido' => 150],
                (object)['codigo' => 'NR-11', 'titulo' => 'Movimentação de Carga', 'preco_sugerido' => 150],
                (object)['codigo' => 'NR-06', 'titulo' => 'EPI', 'preco_sugerido' => 60],
                (object)['codigo' => 'NR-05', 'titulo' => 'CIPA Designada', 'preco_sugerido' => 300],
            ]);

        foreach ($catalogo as $nr) {
            $codigo = $nr->codigo;
            $titulo = $nr->titulo;
            $preco  = property_exists($nr, 'preco_sugerido') ? (float)$nr->preco_sugerido : 0;

            TabelaPrecoItem::updateOrCreate(
                [
                    'tabela_preco_padrao_id' => $tabelaPadraoId,
                    'servico_id'             => $servicoTreinamentoId,
                    'codigo'                 => $codigo,
                ],
                [
                    'descricao' => $titulo,
                    'preco'     => $preco,
                    'ativo'     => true,
                ]
            );
        }

        // cenários de teste: um NR inativo + um com preço 0
        TabelaPrecoItem::updateOrCreate(
            [
                'tabela_preco_padrao_id' => $tabelaPadraoId,
                'servico_id'             => $servicoTreinamentoId,
                'codigo'                 => 'NR-99',
            ],
            [
                'descricao' => 'NR fictícia (inativa p/ teste)',
                'preco'     => 999.99,
                'ativo'     => false,
            ]
        );

        TabelaPrecoItem::updateOrCreate(
            [
                'tabela_preco_padrao_id' => $tabelaPadraoId,
                'servico_id'             => $servicoTreinamentoId,
                'codigo'                 => 'NR-00',
            ],
            [
                'descricao' => 'NR com preço 0 (teste)',
                'preco'     => 0,
                'ativo'     => true,
            ]
        );
    }

    private function seedFaixasEsocial(int $empresaId, int $tabelaPadraoId): void
    {
        $faixas = [
            // [inicio, fim, preco, descricao, ativo]
            [1,  10, 100.00, 'Até 10 colaboradores', true],
            [11, 20, 180.00, '11 a 20 colaboradores', true],
            [21, 30, 220.00, '21 a 30 colaboradores', true],
            [31, 40, 280.00, '31 a 40 colaboradores', true],

            // casos de teste
            [41, 999999, 0.00, 'Acima de 40 (contatar comercial) - preço 0', true],
            [5,  8,  50.00, 'Faixa inativa (sobreposição p/ teste UX)', false],
            [100, 150, 350.00, 'Faixa futura (gap) p/ teste', true],
        ];

        foreach ($faixas as [$inicio, $fim, $preco, $descricao, $ativo]) {
            EsocialTabPreco::updateOrCreate(
                [
                    'empresa_id'            => $empresaId,
                    'tabela_preco_padrao_id'=> $tabelaPadraoId,
                    'inicio'                => $inicio,
                    'fim'                   => $fim,
                ],
                [
                    'preco'     => $preco,
                    'descricao' => $descricao,
                    'ativo'     => $ativo,
                ]
            );
        }
    }
}
