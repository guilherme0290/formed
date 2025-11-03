<?php // app/Flowforge/TarefaBoard.php
namespace App\Flowforge;

use Relaticle\Flowforge\Boards\Board;
use App\Models\Tarefa;
use App\Models\KanbanColuna;

class TarefaBoard extends Board
{
    protected string $model = Tarefa::class;

    public function columns(): array
    {
        // Mapeia colunas do banco
        return KanbanColuna::orderBy('ordem')->get()->map(fn($c)=>[
            'id' => (string) $c->id,
            'label' => $c->nome,
        ])->toArray();
    }

    public function columnField(): string
    {
        // Campo no model Tarefa que identifica a coluna
        return 'coluna_id';
    }

    public function sortField(): ?string
    {
        // Campo no model Tarefa que guarda a ordem dentro da coluna
        return 'ordem';
    }

    public function cardSchema(): array
    {
        // Campos exibidos no card
        return [
            'titulo',
            'prioridade',
            'prazo',
            // opcional: 'responsavel.nome'
        ];
    }
}
