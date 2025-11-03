<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use App\Http\Controllers\KanbanController;
Route::middleware(['auth'])->group(function(){
    Route::get('/operacional/kanban', [KanbanController::class,'index'])->name('operacional.kanban');
    Route::post('/operacional/kanban/mover', [KanbanController::class,'mover'])->name('operacional.kanban.mover');
});
