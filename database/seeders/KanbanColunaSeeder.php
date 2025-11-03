<?php // database/seeders/KanbanColunaSeeder.php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\KanbanColuna;

class KanbanColunaSeeder extends Seeder {
    public function run(): void {
        $empresaId = 1; // ajuste
        $cols = [
            ['nome'=>'A Fazer','ordem'=>1],
            ['nome'=>'Em Progresso','ordem'=>2],
            ['nome'=>'Pausada','ordem'=>3],
            ['nome'=>'Concluída','ordem'=>4,'finaliza'=>true],
            ['nome'=>'Cancelada','ordem'=>5],
        ];
        foreach($cols as $c){
            KanbanColuna::firstOrCreate(
                ['empresa_id'=>$empresaId,'nome'=>$c['nome']],
                ['ordem'=>$c['ordem'],'finaliza'=>$c['finaliza']??false]
            );
        }
    }
}
