<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KanbanColuna;
use App\Models\Tarefa;

class KanbanController extends Controller
{
    public function index(Request $request)
    {
        // Carrega colunas com tarefas básicas (ajuste se tiver escopo por empresa)
        $colunas = KanbanColuna::query()
            ->with(['tarefas' => fn($q) => $q->latest('updated_at')])
            ->orderBy('ordem')
            ->get();

        return view('operacional.kanban', compact('colunas'));
    }
}
