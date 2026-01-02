<?php

namespace Database\Seeders;

use App\Models\TabelaPrecoItem;
use Illuminate\Database\Seeder;

class RemoveAsoTabelaPrecoSeeder extends Seeder
{
    public function run(): void
    {
        $codigos = [
            'ASO-ADM',
            'ASO-DEM',
            'ASO-PER',
            'ASO-FUN',
            'ASO-TRA',
            'ASO-OLD',
        ];

        TabelaPrecoItem::query()
            ->whereIn('codigo', $codigos)
            ->delete();
    }
}
