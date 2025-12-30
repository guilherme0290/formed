<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\ExamesTabPreco;
use App\Models\ProtocoloExame;
use App\Models\ProtocoloExameItem;
use Illuminate\Database\Seeder;

class ExamesProtocolosSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->first();
        if (!$empresa) {
            return;
        }

        $exames = [
            'Exame Clínico',
            'Acuidade visual',
            'Audiometria',
            'Avaliação Psicossocial',
            'Eletrocardiograma (ECG)',
            'Eletroencefalograma (EEG)',
            'Espirometria',
            'Glicemia de Jejum',
            'Hemograma',
            'Raio X Tórax',
            'Raio X',
            'Coprocultura de fezes',
            'Hemograma Completo',
            'Micológico de unha',
            'PPF',
            'VDRL',
        ];

        $examesByTitulo = [];
        foreach ($exames as $titulo) {
            $exame = ExamesTabPreco::firstOrCreate(
                ['empresa_id' => $empresa->id, 'titulo' => $titulo],
                ['descricao' => null, 'preco' => 0, 'ativo' => true]
            );
            $examesByTitulo[$titulo] = $exame->id;
        }

        $protocolos = [
            'Administrativo' => [
                'Exame Clínico',
            ],
            'Trabalho em Altura' => [
                'Exame Clínico',
                'Acuidade visual',
                'Audiometria',
                'Avaliação Psicossocial',
                'Eletrocardiograma (ECG)',
                'Eletroencefalograma (EEG)',
                'Espirometria',
                'Glicemia de Jejum',
                'Hemograma',
                'Raio X Tórax',
            ],
            'Trabalho sem Altura' => [
                'Exame Clínico',
                'Audiometria',
                'Espirometria',
                'Raio X',
                'Avaliação Psicossocial',
            ],
            'Manipulação de alimento' => [
                'Exame Clínico',
                'Coprocultura de fezes',
                'Hemograma Completo',
                'Micológico de unha',
                'PPF',
                'VDRL',
            ],
        ];

        foreach ($protocolos as $titulo => $listaExames) {
            $protocolo = ProtocoloExame::firstOrCreate(
                ['empresa_id' => $empresa->id, 'titulo' => $titulo],
                ['descricao' => null, 'ativo' => true]
            );

            $desiredIds = collect($listaExames)
                ->map(fn ($t) => $examesByTitulo[$t] ?? null)
                ->filter()
                ->values()
                ->all();

            ProtocoloExameItem::where('protocolo_id', $protocolo->id)
                ->whereNotIn('exame_id', $desiredIds)
                ->delete();

            $existing = ProtocoloExameItem::query()
                ->where('protocolo_id', $protocolo->id)
                ->pluck('exame_id')
                ->all();

            $toInsert = array_diff($desiredIds, $existing);
            foreach ($toInsert as $exameId) {
                ProtocoloExameItem::create([
                    'protocolo_id' => $protocolo->id,
                    'exame_id' => $exameId,
                ]);
            }
        }
    }
}
