<?php

use Illuminate\Support\Facades\Route;

// ==================== Controllers ====================
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Master\DashboardController;
use App\Http\Controllers\Master\AcessosController;
use App\Http\Controllers\PapelController;
use App\Http\Controllers\PermissaoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TabelaPrecoController;
use App\Http\Controllers\TabelaPrecoItemController;
use App\Http\Controllers\Api\ServicosApiController;
use App\Http\Controllers\Operacional\FuncionarioController;



use App\Http\Controllers\Operacional\PainelController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Api\ClientesApiController;



// Tela pública de seleção de módulos
Route::get('/entrar', function () {
    return view('entrar');
})->name('entrar');

// ==================== Raiz -> Master ====================
Route::redirect('/', '/entrar');

// ==================== Área autenticada ====================
Route::middleware('auth')->group(function () {

    // ---------- Perfil ----------
    Route::get('/profile',  [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ======================================================
//                  OPERACIONAL
// ======================================================
    Route::prefix('operacional')->name('operacional.')->group(function () {

        Route::get('/kanban', [PainelController::class, 'index'])->name('kanban');

        Route::get('/painel', function () {
            return redirect()->route('operacional.kanban');
        })->name('painel');

        // Drag & Drop
        Route::post('/tarefas/{tarefa}/mover', [PainelController::class, 'mover'])
            ->name('tarefas.mover');

        // ✅ Criar tarefa (cliente existente)
        Route::post('/tarefas/loja/existente', [PainelController::class, 'storeLojaExistente'])
            ->name('tarefas.loja.existente');

        // ✅ Criar tarefa (cliente novo)
        Route::post('/tarefas/loja/novo-cliente', [PainelController::class, 'storeLojaNovo'])
            ->name('tarefas.loja.novo');

        // Funcionários
        Route::get('clientes/{cliente}/funcionarios/novo', [FuncionarioController::class, 'create'])
            ->name('clientes.funcionarios.create');

        Route::post('clientes/{cliente}/funcionarios', [FuncionarioController::class, 'store'])
            ->name('clientes.funcionarios.store');

        // --------------------------------------------------
        // 🔹 Fluxo de criação de Tarefa ASO
        // --------------------------------------------------
        Route::get('/kanban/aso/clientes', [PainelController::class, 'asoSelecionarCliente'])
            ->name('kanban.aso.clientes');

        Route::get('/kanban/aso/clientes/{cliente}/servicos', [PainelController::class, 'asoSelecionarServico'])
            ->name('kanban.aso.servicos');

        Route::get('/kanban/aso/clientes/{cliente}/novo', [PainelController::class, 'asoCreate'])
            ->name('kanban.aso.create');

        Route::post('/kanban/aso/clientes/{cliente}', [PainelController::class, 'asoStore'])
            ->name('kanban.aso.store');

        // --------------------------------------------------
        // 🔹 Fluxo PGR
        // --------------------------------------------------

        // PGR - selecionar tipo (Matriz / Específico)
        Route::get('/kanban/pgr/clientes/{cliente}/tipo', [PainelController::class, 'pgrTipo'])
            ->name('kanban.pgr.tipo');

        // PGR - formulário (recebe ?tipo=matriz ou ?tipo=especifico)
        Route::get('/kanban/pgr/clientes/{cliente}/create', [PainelController::class, 'pgrCreate'])
            ->name('kanban.pgr.create');

        // PGR - salvar formulário (cria a tarefa e o registro em pgr_solicitacoes)
        Route::post('/kanban/pgr/clientes/{cliente}', [PainelController::class, 'pgrStore'])
            ->name('kanban.pgr.store');

        // PGR - pergunta "Precisa de PCMSO?"
        Route::get('/kanban/pgr/{tarefa}/pcmso', [PainelController::class, 'pgrPcmso'])
            ->name('kanban.pgr.pcmso');

        // PGR - salvar resposta PCMSO
        Route::post('/kanban/pgr/{tarefa}/pcmso', [PainelController::class, 'pgrPcmsoStore'])
            ->name('kanban.pgr.pcmso.store');
    });



    // ======================================================
    //                  CLIENTES (CRUD)
    // ======================================================
    Route::prefix('master/clientes')->name('clientes.')->group(function () {
        Route::get('/',               [ClienteController::class, 'index'])->name('index');
        Route::get('/create',         [ClienteController::class, 'create'])->name('create');
        Route::post('/',              [ClienteController::class, 'store'])->name('store');
        Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])->name('edit');
        Route::put('/{cliente}',      [ClienteController::class, 'update'])->name('update');
        Route::delete('/{cliente}',   [ClienteController::class, 'destroy'])->name('destroy');

        // Consulta CNPJ
        Route::get('/consulta-cnpj/{cnpj}', [ClienteController::class, 'consultaCnpj'])
            ->name('consulta-cnpj');
    });

    // Cidades por UF
    Route::get('master/estados/{uf}/cidades', [ClienteController::class, 'cidadesPorUf'])
        ->name('estados.cidades');

    // ======================================================
    //                     MASTER
    // ======================================================
    Route::prefix('master')->name('master.')->group(function () {

        // Dashboard Master
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

        // Usuários
        Route::post('usuarios',                [AcessosController::class, 'usuariosStore'])->name('usuarios.store');
        Route::patch('usuarios/{user}',        [AcessosController::class, 'usuariosUpdate'])->name('usuarios.update');
        Route::delete('usuarios/{user}',       [AcessosController::class, 'usuariosDestroy'])->name('usuarios.destroy');
        Route::post('usuarios/{user}/toggle',  [AcessosController::class, 'usuariosToggle'])->name('usuarios.toggle');
        Route::post('usuarios/{user}/reset',   [AcessosController::class, 'usuariosReset'])->name('usuarios.reset');
        Route::post('usuarios/{user}/password',[AcessosController::class, 'usuariosSetPassword'])->name('usuarios.password');
    });

    // ======================================================
    //                TABELA DE PREÇOS
    // ======================================================
    Route::prefix('master/tabela-precos')->name('tabela-precos.')->group(function () {
        Route::get('/', [TabelaPrecoController::class,'index'])->name('index');

        Route::post('/itens',                 [TabelaPrecoItemController::class,'store'])->name('items.store');
        Route::put('/itens/{item}',           [TabelaPrecoItemController::class,'update'])->name('items.update');
        Route::patch('/itens/{item}/toggle',  [TabelaPrecoItemController::class,'toggle'])->name('items.toggle');
        Route::delete('/itens/{item}',        [TabelaPrecoItemController::class,'destroy'])->name('items.destroy');
    });

    // ======================================================
    //                    APIs internas
    // ======================================================
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/clientes', [ClientesApiController::class, 'index'])->name('clientes.index');
        Route::get('/servicos', [ServicosApiController::class, 'index'])->name('servicos.index');
    });
});

require __DIR__.'/auth.php';
