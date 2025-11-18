<?php

use Illuminate\Support\Facades\Route;

// ==================== Controllers ====================
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KanbanController;
use App\Http\Controllers\Master\DashboardController;
use App\Http\Controllers\Master\AcessosController;
use App\Http\Controllers\PapelController;
use App\Http\Controllers\PermissaoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TabelaPrecoController;
use App\Http\Controllers\TabelaPrecoItemController;
use App\Http\Controllers\Api\ServicosApiController;

use App\Http\Controllers\Operacional\PainelController;
use App\Http\Controllers\Operacional\TarefaLojaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Api\ClientesApiController;

// ==================== Raiz -> Master ====================
Route::redirect('/', '/master');

// ==================== Área autenticada ====================
Route::middleware('auth')->group(function () {

    // ---------- Perfil ----------
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ---------- Operacional ----------
    Route::prefix('operacional')->name('operacional.')->group(function () {
        Route::get('/kanban', [KanbanController::class, 'index'])->name('kanban');
        Route::get('/painel', [PainelController::class, 'index'])->name('painel');

        Route::post('/tarefas/loja/existente',    [TarefaLojaController::class, 'storeExistente'])->name('tarefas.loja.existente');
        Route::post('/tarefas/loja/novo-cliente', [TarefaLojaController::class, 'storeNovoCliente'])->name('tarefas.loja.novo');
    });

    // ---------- CLIENTES (CRUD sob /master/clientes, nomes clientes.*) ----------
    Route::prefix('master/clientes')->name('clientes.')->group(function () {
        Route::get('/',               [ClienteController::class, 'index'])->name('index');
        Route::get('/create',         [ClienteController::class, 'create'])->name('create');
        Route::post('/',              [ClienteController::class, 'store'])->name('store');
        Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])->name('edit');
        Route::put('/{cliente}',      [ClienteController::class, 'update'])->name('update');
        Route::delete('/{cliente}',   [ClienteController::class, 'destroy'])->name('destroy');

        // Consulta CNPJ (usada no formulário, se quiser usar depois via AJAX)
        Route::get('/consulta-cnpj/{cnpj}', [ClienteController::class, 'consultaCnpj'])
            ->name('consulta-cnpj');
    });

    // ---------- CIDADES POR UF (usada no CEP e no select de estado) ----------
    // URL: /master/estados/{UF}/cidades
    Route::get('master/estados/{uf}/cidades', [ClienteController::class, 'cidadesPorUf'])
        ->name('estados.cidades');

    // ---------- Master ----------
    Route::prefix('master')->name('master.')->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Acessos
        Route::get('/acessos', [AcessosController::class, 'index'])->name('acessos');

        // Papéis
        Route::resource('papeis', PapelController::class)
            ->parameters(['papeis' => 'papel'])
            ->only(['index','store','update','destroy']);

        // Permissões
        Route::resource('permissoes', PermissaoController::class)
            ->parameters(['permissoes' => 'permissao'])
            ->only(['index','store','update','destroy']);

        // Usuários (ações rápidas)
        Route::post('usuarios',                [AcessosController::class,'usuariosStore'])->name('usuarios.store');
        Route::patch('usuarios/{user}',        [AcessosController::class,'usuariosUpdate'])->name('usuarios.update');
        Route::delete('usuarios/{user}',       [AcessosController::class,'usuariosDestroy'])->name('usuarios.destroy');
        Route::post('usuarios/{user}/toggle',  [AcessosController::class,'usuariosToggle'])->name('usuarios.toggle');
        Route::post('usuarios/{user}/reset',   [AcessosController::class,'usuariosReset'])->name('usuarios.reset');
        Route::post('usuarios/{user}/password',[AcessosController::class,'usuariosSetPassword'])->name('usuarios.password');
    });

    // ---------- Tabela de Preços (URLs sob /master, nomes sem prefixo master) ----------
    Route::prefix('master/tabela-precos')->name('tabela-precos.')->group(function () {
        Route::get('/', [TabelaPrecoController::class,'index'])->name('index');

        Route::post('/itens',                 [TabelaPrecoItemController::class,'store'])->name('items.store');
        Route::put('/itens/{item}',           [TabelaPrecoItemController::class,'update'])->name('items.update');
        Route::patch('/itens/{item}/toggle',  [TabelaPrecoItemController::class,'toggle'])->name('items.toggle');
        Route::delete('/itens/{item}',        [TabelaPrecoItemController::class,'destroy'])->name('items.destroy');
    });

    // ---------- APIs internas ----------
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/clientes', [ClientesApiController::class, 'index'])->name('clientes.index');
        Route::get('/servicos', [ServicosApiController::class,'index'])->name('servicos.index');
    });
});

require __DIR__.'/auth.php';
