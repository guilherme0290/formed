<?php

use App\Http\Controllers\Api\ClientesApiController;
use App\Http\Controllers\Api\ServicosApiController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Master\AcessosController;
use App\Http\Controllers\Master\DashboardController;
use App\Http\Controllers\Operacional\AsoController;
use App\Http\Controllers\Operacional\FuncionarioController;
use App\Http\Controllers\Operacional\LtipController;
use App\Http\Controllers\Operacional\PainelController;
use App\Http\Controllers\Operacional\PcmsoController;
use App\Http\Controllers\Operacional\PgrController;
use App\Http\Controllers\PapelController;
use App\Http\Controllers\PermissaoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TabelaPrecoController;
use App\Http\Controllers\TabelaPrecoItemController;
use App\Http\Controllers\Operacional\LtcatController;
use App\Http\Controllers\Operacional\AprController;
use App\Http\Controllers\Operacional\PaeController;
use App\Http\Controllers\Operacional\TreinamentoNrController;
use App\Http\Controllers\FuncaoController;
use App\Http\Controllers\TarefaController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cliente\ClienteDashboardController;
use App\Http\Controllers\Cliente\ClienteFuncionarioController;

// ==================== Controllers ====================


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

        // ======================================================
        //  DASHBOARD / KANBAN
        // ======================================================
        Route::get('/kanban', [PainelController::class, 'index'])->name('kanban');

        Route::get('/painel', function () {
            return redirect()->route('operacional.kanban');
        })->name('painel');

        Route::post('/funcoes/store-ajax', [FuncaoController::class, 'storeAjax'])
            ->name('funcoes.store-ajax');

        // Drag & Drop Kanban
        Route::post('/tarefas/{tarefa}/mover', [PainelController::class, 'mover'])
            ->name('tarefas.mover');

        Route::post('/tarefas/{tarefa}/observacao', [
            PainelController::class,
            'salvarObservacao'
        ])->name('tarefas.observacao');

        Route::delete('/tarefas/{tarefa}', [PainelController::class, 'destroy'])
            ->name('operacional.tarefas.destroy');


        Route::post('/tarefas/{tarefa}/finalizar-com-arquivo',
            [TarefaController::class, 'finalizarComArquivo']
        )->name('tarefas.finalizar-com-arquivo');


        // ======================================================
        //  FUNCIONÁRIOS DO CLIENTE
        // ======================================================
        Route::get('clientes/{cliente}/funcionarios/novo', [FuncionarioController::class, 'create'])
            ->name('clientes.funcionarios.create');

        Route::post('clientes/{cliente}/funcionarios', [FuncionarioController::class, 'store'])
            ->name('clientes.funcionarios.store');

        // ======================================================
        //  ASO
        // ======================================================
        Route::get('/kanban/aso/clientes', [PainelController::class, 'asoSelecionarCliente'])
            ->name('kanban.aso.clientes');

        Route::get('/kanban/clientes/{cliente}/servicos', [PainelController::class, 'selecionarServico'])
            ->name('kanban.servicos');

        Route::get('/kanban/aso/clientes/{cliente}/novo', [AsoController::class, 'asoCreate'])
            ->name('kanban.aso.create');

        Route::post('/kanban/aso/clientes/{cliente}', [AsoController::class, 'asoStore'])
            ->name('kanban.aso.store');

        Route::get('/tarefas/{tarefa}/editar', [AsoController::class, 'edit'])
            ->name('kanban.aso.editar');

        Route::put('/kanban/aso/{tarefa}', [AsoController::class, 'update'])->name('kanban.aso.update');

        // ======================================================
        //  PGR
        // ======================================================

        // Selecionar tipo (Matriz / Específico)
        Route::get('/kanban/pgr/clientes/{cliente}/tipo', [PgrController::class, 'pgrTipo'])
            ->name('kanban.pgr.tipo');

        // Formulário (recebe ?tipo=matriz ou ?tipo=especifico)
        Route::get('/kanban/pgr/clientes/{cliente}/create', [PgrController::class, 'pgrCreate'])
            ->name('kanban.pgr.create');

        // Salvar formulário (cria a tarefa e o registro em pgr_solicitacoes)
        Route::post('/kanban/pgr/clientes/{cliente}', [PgrController::class, 'pgrStore'])
            ->name('kanban.pgr.store');

        // Pergunta "Precisa de PCMSO?"
        Route::get('/kanban/pgr/{tarefa}/pcmso', [PgrController::class, 'pgrPcmso'])
            ->name('kanban.pgr.pcmso');

        // Salvar resposta PCMSO
        Route::post('/kanban/pgr/{tarefa}/pcmso', [PgrController::class, 'pgrPcmsoStore'])
            ->name('kanban.pgr.pcmso.store');

        Route::get('/kanban/pgr/{tarefa}/editar', [PgrController::class, 'pgrEdit'])
            ->name('kanban.pgr.editar');

        Route::put('/kanban/pgr/{tarefa}', [PgrController::class, 'pgrUpdate'])
            ->name('kanban.pgr.update');



        // ======================================================
        //  PCMSO
        // ======================================================

        // Selecionar tipo (Matriz / Específico)
        Route::get('clientes/{cliente}/pcmso/tipo', [PcmsoController::class, 'selecionarTipo'])
            ->name('pcmso.tipo');

        // Pergunta se possui PGR (para matriz/específico)
        Route::get('clientes/{cliente}/pcmso/{tipo}/possui-pgr', [PcmsoController::class, 'perguntaPgr'])
            ->name('pcmso.possui-pgr');

        // Formulário para anexar PGR (matriz/específico)
        Route::get('clientes/{cliente}/pcmso/{tipo}/inserir-pgr', [PcmsoController::class, 'createComPgr'])
            ->name('pcmso.create-com-pgr');

        // Salvar PCMSO com PGR anexado
        Route::post('clientes/{cliente}/pcmso/{tipo}/inserir-pgr', [PcmsoController::class, 'storeComPgr'])
            ->name('pcmso.store-com-pgr');

        Route::get('kanban/pcmso/{tarefa}/editar', [PcmsoController::class, 'edit'])
            ->name('kanban.pcmso.edit');

        Route::put('kanban/pcmso/{tarefa}', [PcmsoController::class, 'update'])
            ->name('kanban.pcmso.update');

        // ======================================================
        //  LTCAT
        // ======================================================

        // Selecionar tipo (Matriz | Específico)
        Route::get('clientes/{cliente}/ltcat/tipo', [LtcatController::class, 'tipo'])
            ->name('ltcat.tipo');

        // Formulário (?tipo=matriz ou ?tipo=especifico)
        Route::get('clientes/{cliente}/ltcat/create', [LtcatController::class, 'create'])
            ->name('ltcat.create');

        // Salvar LTCAT + criar tarefa
        Route::post('clientes/{cliente}/ltcat', [LtcatController::class, 'store'])
            ->name('ltcat.store');

        Route::get('ltcat/editar/{tarefa}', [LtcatController::class, 'edit'])
            ->name('ltcat.edit');

        // NOVO: atualizar LTCAT
        Route::put('ltcat/{ltcat}', [LtcatController::class, 'update'])
            ->name('ltcat.update');

        // ======================================================
        //  LTIP
        // ======================================================
        Route::get('clientes/{cliente}/ltip', [LtipController::class, 'create'])
            ->name('ltip.create');

        Route::post('clientes/{cliente}/ltip', [LtipController::class, 'store'])
            ->name('ltip.store');

        Route::get('ltip/{tarefa}/edit', [LtipController::class, 'edit'])
            ->name('ltip.edit');

        // LTIP - Atualizar (recebe o PUT do form)
        Route::put('ltip/{ltip}', [LtipController::class, 'update'])
            ->name('ltip.update');

        // ======================================================
        //  APR
        // ======================================================
        Route::get('clientes/{cliente}/apr', [AprController::class, 'create'])
            ->name('apr.create');

        Route::post('clientes/{cliente}/apr', [AprController::class, 'store'])
            ->name('apr.store');

        // APR - Editar (abre o form a partir da tarefa)
        Route::get('apr/{tarefa}/edit', [AprController::class, 'edit'])
            ->name('apr.edit');

        // APR - Atualizar (PUT)
        Route::put('apr/{apr}', [AprController::class, 'update'])
            ->name('apr.update');

        // ======================================================
        //  PAE
        // ======================================================
        Route::get('clientes/{cliente}/pae', [PaeController::class, 'create'])
            ->name('pae.create');

        Route::post('clientes/{cliente}/pae', [PaeController::class, 'store'])
            ->name('pae.store');

        // PAE - editar (a partir da tarefa)
        Route::get('pae/{tarefa}/edit', [PaeController::class, 'edit'])
            ->name('pae.edit');

        // PAE - update
        Route::put('pae/{pae}', [PaeController::class, 'update'])
            ->name('pae.update');

        // ======================================================
        //  TREINAMENTOS NRs
        // ======================================================

        // Tela de seleção / criação de solicitação
        Route::get('clientes/{cliente}/treinamentos-nr', [TreinamentoNrController::class, 'create'])
            ->name('treinamentos-nr.create');

        // Salvar solicitação de treinamento NR
        Route::post('clientes/{cliente}/treinamentos-nr', [TreinamentoNrController::class, 'store'])
            ->name('treinamentos-nr.store');

        Route::get('treinamentos-nr/tarefa/{tarefa}/edit', [TreinamentoNrController::class, 'edit'])
            ->name('treinamentos-nr.edit');

        // Atualizar treinamento
        Route::put('treinamentos-nr/tarefa/{tarefa}', [TreinamentoNrController::class, 'update'])
            ->name('treinamentos-nr.update');

        // AJAX: cadastrar novo funcionário e devolver JSON
        Route::post(
            'clientes/{cliente}/treinamentos-nr/funcionarios',
            [TreinamentoNrController::class, 'storeFuncionario']
        )->name('treinamentos-nr.funcionarios.store');

        Route::post('funcoes', [FuncaoController::class, 'storefast'])
            ->name('funcoes.store-ajax');
    });


    Route::get('operacional/tarefas/detalhes/ajax',
        [PainelController::class, 'detalhesAjax']
    )->name('operacional.tarefas.detalhes.ajax');


    // ======================================================
    //                  CLIENTE (PAINEL)
    // ======================================================
    Route::prefix('cliente')
        ->name('cliente.')
        ->middleware(['auth']) // ajuste o middleware que você já usa
        ->group(function () {

            Route::get('/', [ClienteDashboardController::class, 'index'])
                ->name('dashboard');

            // Funcionários
            Route::get('/funcionarios', [ClienteFuncionarioController::class, 'index'])
                ->name('funcionarios.index');

            Route::get('/funcionarios/novo', [ClienteFuncionarioController::class, 'create'])
                ->name('funcionarios.create');

            Route::post('/funcionarios', [ClienteFuncionarioController::class, 'store'])
                ->name('funcionarios.store');

            Route::get('/funcionarios/{funcionario}', [ClienteFuncionarioController::class, 'show'])
                ->name('funcionarios.show');
        });

    // ======================================================


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

        //

        // CRUD de Funções
        Route::get('funcoes',        [FuncaoController::class, 'index'])->name('funcoes.index');
        Route::post('funcoes',       [FuncaoController::class, 'store'])->name('funcoes.store');
        Route::put('funcoes/{funcao}',   [FuncaoController::class, 'update'])->name('funcoes.update');
        Route::delete('funcoes/{funcao}',[FuncaoController::class, 'destroy'])->name('funcoes.destroy');
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
