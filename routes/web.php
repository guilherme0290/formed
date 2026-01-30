<?php

use App\Http\Controllers\Api\ClientesApiController;
use App\Http\Controllers\Api\ServicosApiController;
use App\Http\Controllers\Cliente\ArquivoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\Comercial\EsocialFaixaController;
use App\Http\Controllers\Comercial\ExamesTabPrecoController;
use App\Http\Controllers\Comercial\ClienteGheController;
use App\Http\Controllers\Comercial\ProtocolosExamesController;
use App\Http\Controllers\Comercial\PropostaPrecoController;
use App\Http\Controllers\Master\AcessosController;
use App\Http\Controllers\Master\DashboardController as DashboardMaster;
use App\Http\Controllers\Master\EmailCaixaController;
use App\Http\Controllers\Master\AgendaVendedorController;
use App\Http\Controllers\Master\DashboardPreferenceController;
use App\Http\Controllers\Comercial\DashboardController as DashboardComercial;
use App\Http\Controllers\Comercial\FuncoesController as ComercialFuncoesController;
use App\Http\Controllers\Operacional\AsoController;
use App\Http\Controllers\Operacional\FuncionarioController;
use App\Http\Controllers\Operacional\LtipController;
use App\Http\Controllers\Operacional\PainelController;
use App\Http\Controllers\Operacional\PcmsoController;
use App\Http\Controllers\Operacional\PgrController;
use App\Http\Controllers\PapelController;
use App\Http\Controllers\PermissaoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Comercial\TabelaPrecoController;
use App\Http\Controllers\Operacional\LtcatController;
use App\Http\Controllers\Operacional\AprController;
use App\Http\Controllers\Operacional\PaeController;
use App\Http\Controllers\Operacional\TreinamentoNrController;
use App\Http\Controllers\Comercial\TreinamentoNrController as ComercialTreinamentoNrController;
use App\Http\Controllers\FuncaoController;
use App\Http\Controllers\PropostaPublicController;
use App\Http\Controllers\TarefaController;
use App\Http\Controllers\AnexoController;
use App\Models\Servico;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cliente\ClienteDashboardController;
use App\Http\Controllers\Cliente\ClienteFuncionarioController;
use Illuminate\Http\Request;
use App\Models\Cliente;
use \App\Http\Controllers\Comercial\PropostaController;


// ==================== Controllers ====================


// Proposta pública (sem login)
Route::get('/proposta/{token}', [PropostaPublicController::class, 'show'])
    ->name('propostas.public.show');
Route::post('/proposta/{token}/responder', [PropostaPublicController::class, 'responder'])
    ->name('propostas.public.responder');

// ==================== Raiz -> Login ====================
Route::redirect('/', '/login');

// ==================== Área autenticada ====================
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $user = $request->user();
        $papelNome = mb_strtolower(optional($user->papel)->nome ?? '');

        if ($user->must_change_password ?? false) {
            return redirect()->route('password.force');
        }

        if ($papelNome === 'master') {
            return redirect()->route('master.dashboard');
        }

        if ($papelNome === 'operacional') {
            return redirect()->route('operacional.kanban');
        }

        if ($papelNome === 'financeiro') {
            return redirect()->route('financeiro.dashboard');
        }

        if ($papelNome === 'comercial') {
            return redirect()->route('comercial.dashboard');
        }

        if ($papelNome === 'cliente') {
            if ($user->cliente_id) {
                $request->session()->put('portal_cliente_id', $user->cliente_id);
                return redirect()->route('cliente.dashboard');
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Seu usuario nao esta vinculado a nenhum cliente.');
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()
            ->view('errors.perfil-nao-suportado', ['papel' => $papelNome ?: 'desconhecido'], 403);
    })->name('dashboard');

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

        Route::prefix('tarefas')->name('tarefas.')->group(function () {
            // Drag & Drop Kanban
            Route::post('{tarefa}/mover', [PainelController::class, 'mover'])
                ->name('mover');

            Route::post('prazos', [PainelController::class, 'prazos'])
                ->name('prazos');

            Route::post('{tarefa}/observacao', [
                PainelController::class,
                'salvarObservacao'
            ])->name('observacao');

            Route::delete('{tarefa}', [PainelController::class, 'destroy'])
                ->name('destroy');

            Route::post('{tarefa}/finalizar-com-arquivo',
                [TarefaController::class, 'finalizarComArquivo']
            )->name('finalizar-com-arquivo');

            Route::post('{tarefa}/anexos', [AnexoController::class, 'store'])
                ->name('anexos.store');

            Route::get('detalhes/ajax', [PainelController::class, 'detalhesAjax'])
                ->name('detalhes.ajax');
        });

        Route::prefix('anexos')->name('anexos.')->group(function () {
            Route::get('{anexo}/view', [AnexoController::class, 'view'])
                ->name('view');

            Route::get('{anexo}/download', [AnexoController::class, 'download'])
                ->name('download');

            Route::delete('{anexo}', [AnexoController::class, 'destroy'])
                ->name('destroy');
        });


        // ======================================================
        //  FUNCIONÁRIOS DO CLIENTE
        // ======================================================
        Route::get('clientes/{cliente}/funcionarios/novo', [FuncionarioController::class, 'create'])
            ->name('clientes.funcionarios.create');

        Route::post('clientes/{cliente}/funcionarios', [FuncionarioController::class, 'store'])
            ->name('clientes.funcionarios.store');

        Route::prefix('kanban')->name('kanban.')->group(function () {
            // ======================================================
            //  ASO
            // ======================================================
            Route::get('/aso/clientes', [PainelController::class, 'asoSelecionarCliente'])
                ->name('aso.clientes');

            Route::get('/clientes/{cliente}/servicos', [PainelController::class, 'selecionarServico'])
                ->name('servicos');

            Route::get('/aso/clientes/{cliente}/novo', [AsoController::class, 'asoCreate'])
                ->name('aso.create');

            Route::post('/aso/clientes/{cliente}', [AsoController::class, 'asoStore'])
                ->name('aso.store');

            Route::put('/aso/{tarefa}', [AsoController::class, 'update'])->name('aso.update');

            // ======================================================
            //  PGR
            // ======================================================

            // Selecionar tipo (Matriz / Específico)
            Route::get('/pgr/clientes/{cliente}/tipo', [PgrController::class, 'pgrTipo'])
                ->name('pgr.tipo');

            // Formulário (recebe ?tipo=matriz ou ?tipo=especifico)
            Route::get('/pgr/clientes/{cliente}/create', [PgrController::class, 'pgrCreate'])
                ->name('pgr.create');

            // Salvar formulário (cria a tarefa e o registro em pgr_solicitacoes)
            Route::post('/pgr/clientes/{cliente}', [PgrController::class, 'pgrStore'])
                ->name('pgr.store');

            // Pergunta "Precisa de PCMSO?"
            Route::get('/pgr/{tarefa}/pcmso', [PgrController::class, 'pgrPcmso'])
                ->name('pgr.pcmso');

            // Salvar resposta PCMSO
            Route::post('/pgr/{tarefa}/pcmso', [PgrController::class, 'pgrPcmsoStore'])
                ->name('pgr.pcmso.store');

            Route::get('/pgr/{tarefa}/editar', [PgrController::class, 'pgrEdit'])
                ->name('pgr.editar');

            Route::put('/pgr/{tarefa}', [PgrController::class, 'pgrUpdate'])
                ->name('pgr.update');

            // ======================================================
            //  PCMSO
            // ======================================================
            Route::get('/pcmso/{tarefa}/editar', [PcmsoController::class, 'edit'])
                ->name('pcmso.edit');

            Route::put('/pcmso/{tarefa}', [PcmsoController::class, 'update'])
                ->name('pcmso.update');
        });

        Route::get('/tarefas/{tarefa}/editar', [AsoController::class, 'edit'])
            ->name('kanban.aso.editar');

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

        // ======================================================
        //  LTCAT
        // ======================================================

        // Selecionar tipo (Matriz | Específico)
        Route::get('clientes/{cliente}/ltcat/tipo', [LtcatController::class, 'selecionarTipo'])
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

        Route::post('funcoes', [FuncaoController::class, 'storefast']);
      });

    // ======================================================
    //                  CLIENTE (PAINEL)
    // ======================================================
    $portalServicoPermitido = function (int $clienteId, string $servicoNome): bool {
        $cliente = Cliente::find($clienteId);
        if (!$cliente) {
            return false;
        }

        $servicoId = Servico::where('empresa_id', $cliente->empresa_id)
            ->where('nome', $servicoNome)
            ->value('id');

        if (!$servicoId) {
            return false;
        }

        $hoje = now()->toDateString();
        $contrato = \App\Models\ClienteContrato::query()
            ->where('empresa_id', $cliente->empresa_id)
            ->where('cliente_id', $cliente->id)
            ->where('status', 'ATIVO')
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_inicio')->orWhereDate('vigencia_inicio', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('vigencia_fim')->orWhereDate('vigencia_fim', '>=', $hoje);
            })
            ->first();

        if (!$contrato) {
            return false;
        }

        if (mb_strtolower($servicoNome) === 'aso') {
            $tiposAsoPermitidos = app(\App\Services\AsoGheService::class)
                ->resolveTiposAsoContrato($contrato);
            if (empty($tiposAsoPermitidos)) {
                return false;
            }
        }

        return $contrato->itens()
            ->where('servico_id', $servicoId)
            ->where('ativo', true)
            ->where('preco_unitario_snapshot', '>', 0)
            ->exists();
    };
    $portalClienteAtual = function (): Cliente {
        $clienteId = (int) session('portal_cliente_id');
        return Cliente::findOrFail($clienteId);
    };
    Route::prefix('cliente')
        ->name('cliente.')
        ->group(function () use ($portalServicoPermitido, $portalClienteAtual) {

            Route::get('/', [ClienteDashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/faturas', [ClienteDashboardController::class, 'faturas'])
                ->name('faturas');

            Route::get('/andamento', function () {
                return redirect()->route('cliente.faturas');
            })->name('andamento');

            Route::prefix('funcionarios')->name('funcionarios.')->group(function () {
                Route::get('/', [ClienteFuncionarioController::class, 'index'])
                    ->name('index');

                Route::get('/novo', [ClienteFuncionarioController::class, 'create'])
                    ->name('create');

                Route::post('/', [ClienteFuncionarioController::class, 'store'])
                    ->name('store');

                Route::get('/{funcionario}/editar', [ClienteFuncionarioController::class, 'edit'])
                    ->name('edit');

                Route::put('/{funcionario}', [ClienteFuncionarioController::class, 'update'])
                    ->name('update');

                Route::get('/{funcionario}', [ClienteFuncionarioController::class, 'show'])
                    ->name('show');

                // 🔁 Ativar / Inativar funcionário (portal do cliente)
                Route::patch('/{funcionario}/toggle-status',
                    [ClienteFuncionarioController::class, 'toggleStatus']
                )->name('toggle-status');
            });

            // Meus Arquivos
            Route::get('/arquivos', [ArquivoController::class, 'index'])
                ->name('arquivos.index');

            Route::prefix('servicos')
                ->name('servicos.')
                ->group(function () use ($portalServicoPermitido, $portalClienteAtual) {
                    Route::get('/aso', function () use ($portalServicoPermitido, $portalClienteAtual) {
                        $cliente = $portalClienteAtual();

                        if (!$portalServicoPermitido($cliente->id, 'ASO')) {
                            return redirect()
                                ->route('cliente.dashboard')
                                ->with('error', 'Serviço ASO não disponível no contrato ativo.');
                        }

                        return redirect()->route('operacional.kanban.aso.create', [
                            'cliente' => $cliente,
                            'origem'  => 'cliente',
                        ]);
                    })->name('aso');

                    Route::get('/pgr', function () use ($portalServicoPermitido, $portalClienteAtual) {
                        $cliente = $portalClienteAtual();

                        if (!$portalServicoPermitido($cliente->id, 'PGR')) {
                            return redirect()
                                ->route('cliente.dashboard')
                                ->with('error', 'Serviço PGR não disponível no contrato ativo.');
                        }

                        return redirect()->route('operacional.kanban.pgr.tipo', [
                            'cliente' => $cliente->id,
                            'origem'  => 'cliente'
                        ]);
                    })->name('pgr');

                    Route::get('/pcmso', function () use ($portalServicoPermitido, $portalClienteAtual) {
                        $cliente = $portalClienteAtual();

                        if (!$portalServicoPermitido($cliente->id, 'PCMSO')) {
                            return redirect()
                                ->route('cliente.dashboard')
                                ->with('error', 'Serviço PCMSO não disponível no contrato ativo.');
                        }

                        return redirect()->route('operacional.pcmso.tipo', [
                            'cliente' => $cliente,
                            'origem'  => 'cliente',
                        ]);
                    })->name('pcmso');

                    Route::get('/ltcat', function () use ($portalServicoPermitido, $portalClienteAtual) {
                        $cliente = $portalClienteAtual();

                        if (!$portalServicoPermitido($cliente->id, 'LTCAT')) {
                            return redirect()
                                ->route('cliente.dashboard')
                                ->with('error', 'Serviço LTCAT não disponível no contrato ativo.');
                        }

                        return redirect()->route('operacional.ltcat.tipo', [
                            'cliente' => $cliente->id,
                            'origem'  => 'cliente',
                        ]);
                    })->name('ltcat');

                    Route::get('/apr', function () use ($portalServicoPermitido, $portalClienteAtual) {
                        $cliente = $portalClienteAtual();

                        if (!$portalServicoPermitido($cliente->id, 'APR')) {
                            return redirect()
                                ->route('cliente.dashboard')
                                ->with('error', 'Serviço APR não disponível no contrato ativo.');
                        }

                        return redirect()->route('operacional.apr.create', [
                            'cliente' => $cliente->id,
                            'origem'  => 'cliente',
                        ]);
                    })->name('apr');

                    Route::get('/treinamentos', function () use ($portalServicoPermitido, $portalClienteAtual) {
                        $cliente = $portalClienteAtual();

                        if (!$portalServicoPermitido($cliente->id, 'Treinamentos NRs')) {
                            return redirect()
                                ->route('cliente.dashboard')
                                ->with('error', 'Serviço Treinamentos NRs não disponível no contrato ativo.');
                        }

                        return redirect()->route('operacional.treinamentos-nr.create', [
                            'cliente' => $cliente->id,
                            'origem'  => 'cliente',
                        ]);
                    })->name('treinamentos');
                });

    });


    Route::middleware(['auth'])
        ->prefix('comercial')
        ->name('comercial.')
        ->group(function () {

            // Painel Comercial
            Route::get('/', [DashboardComercial::class, 'index'])
                ->name('dashboard');

            // Apresentação de Proposta
            Route::get('/apresentacao', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'cliente'])
                ->name('apresentacao.cliente');
            Route::post('/apresentacao', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'clienteStore'])
                ->name('apresentacao.cliente.store');
            Route::get('/apresentacao/segmento', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'segmento'])
                ->name('apresentacao.segmento');
            Route::post('/apresentacao/logo', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'clienteLogoStore'])
                ->name('apresentacao.logo');
            Route::post('/apresentacao/logo/remover', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'clienteLogoDestroy'])
                ->name('apresentacao.logo.destroy');
            Route::get('/apresentacao/{segmento}/modelo', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'modelo'])
                ->name('apresentacao.modelo');
            Route::post('/apresentacao/{segmento}/modelo', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'modeloStore'])
                ->name('apresentacao.modelo.store');
            Route::get('/apresentacao/{segmento}', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'show'])
                ->name('apresentacao.show');
            Route::get('/apresentacao/{segmento}/pdf', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'pdf'])
                ->name('apresentacao.pdf');
            Route::get('/apresentacao-cancelar', [\App\Http\Controllers\Comercial\ApresentacaoController::class, 'cancelar'])
                ->name('apresentacao.cancelar');

            // Propostas
            Route::prefix('propostas')->name('propostas.')->group(function () {
                Route::get('/', [PropostaController::class, 'index'])
                    ->name('index');

                Route::get('/criar', [PropostaController::class, 'create'])
                    ->name('create');

                Route::post('/', [PropostaController::class, 'store'])
                    ->name('store');

                Route::get('/preco-servico/{servico}', [PropostaPrecoController::class, 'precoServico'])
                    ->name('preco-servico');

                Route::get('/preco-treinamento/{codigo}', [PropostaPrecoController::class, 'precoTreinamento'])
                    ->name('preco-treinamento');

                Route::get('/esocial-preco/{qtd}', [PropostaPrecoController::class, 'esocialPreco'])
                    ->name('esocial-preco');

                Route::get('/treinamentos-nrs.json', [PropostaPrecoController::class, 'treinamentosJson'])
                    ->name('treinamentos-nrs.json');

                Route::post('/{proposta}/enviar-whatsapp', [PropostaController::class, 'enviarWhatsapp'])
                    ->name('enviar-whatsapp');

                Route::post('/{proposta}/enviar-email', [PropostaController::class, 'enviarEmail'])
                    ->name('enviar-email');

                Route::post('/{proposta}/status', [PropostaController::class, 'alterarStatus'])
                    ->name('status');

                Route::post('/{proposta}/fechar', [PropostaController::class, 'fechar'])
                    ->name('fechar');

                Route::get('/{proposta}/editar', [PropostaController::class, 'edit'])
                    ->name('edit');

                Route::put('/{proposta}', [PropostaController::class, 'update'])
                    ->name('update');

                Route::delete('/{proposta}', [PropostaController::class, 'destroy'])
                    ->name('destroy');

                Route::get('/{proposta}/pdf', [PropostaController::class, 'pdf'])
                    ->name('pdf');
                Route::get('/{proposta}/imprimir', [PropostaController::class, 'print'])
                    ->name('print');

                Route::get('/{proposta}', [PropostaController::class, 'show'])
                    ->name('show');
            });

            // Contratos
            Route::prefix('contratos')->name('contratos.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Comercial\ContratoController::class, 'index'])
                    ->name('index');
                Route::get('/{contrato}', [\App\Http\Controllers\Comercial\ContratoController::class, 'show'])
                    ->name('show');
                Route::get('/{contrato}/vigencia', [\App\Http\Controllers\Comercial\ContratoController::class, 'novaVigencia'])
                    ->name('vigencia');
                Route::post('/{contrato}/vigencia', [\App\Http\Controllers\Comercial\ContratoController::class, 'storeVigencia'])
                    ->name('vigencia.store');
            });

            // Clientes (acesso comercial)
            Route::prefix('clientes')->name('clientes.')->group(function () {
                Route::get('/',               [ClienteController::class, 'index'])->name('index');
                Route::get('/create',         [ClienteController::class, 'create'])->name('create');
                Route::post('/',              [ClienteController::class, 'store'])->name('store');
                Route::get('/{cliente}',      [ClienteController::class, 'show'])->name('show');
                Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])->name('edit');
                Route::put('/{cliente}',      [ClienteController::class, 'update'])->name('update');
                Route::delete('/{cliente}',   [ClienteController::class, 'destroy'])->name('destroy');
                Route::get('/{cliente}/acesso', [ClienteController::class, 'acessoForm'])->name('acesso.form');
                Route::post('/{cliente}/acesso', [ClienteController::class, 'criarAcesso'])->name('acesso');

                // Consulta CNPJ
                Route::get('/consulta-cnpj/{cnpj}', [ClienteController::class, 'consultaCnpj'])
                    ->name('consulta-cnpj');
                Route::get('/cnpj-exists/{cnpj}', [ClienteController::class, 'cnpjExists'])
                    ->name('cnpj-exists');
            });

            // Funções (CRUD Comercial)
            Route::prefix('funcoes')->name('funcoes.')->group(function () {
                Route::get('/', [ComercialFuncoesController::class, 'index'])
                    ->name('index');
                Route::post('/', [ComercialFuncoesController::class, 'store'])
                    ->name('store');
                Route::put('/{funcao}', [ComercialFuncoesController::class, 'update'])
                    ->name('update');
                Route::delete('/{funcao}', [ComercialFuncoesController::class, 'destroy'])
                    ->name('destroy');
            });

            // Kanban de Propostas (Acompanhamento)
            Route::prefix('pipeline')->name('pipeline.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Comercial\PipelineController::class, 'index'])
                    ->name('index');
                Route::post('/propostas/{proposta}/mover', [\App\Http\Controllers\Comercial\PipelineController::class, 'mover'])
                    ->name('mover');
            });

            //tabela de preco
            Route::name('tabela-precos.')->group(function () {
                Route::get('/tabela-precos', [TabelaPrecoController::class, 'itensIndex'])
                    ->name('index');

                Route::post('/tabela-precos', [TabelaPrecoController::class, 'update'])
                    ->name('update');

                //tabela de preco ITENS
                Route::prefix('itens')->name('itens.')->group(function () {
                    Route::get('/', [TabelaPrecoController::class, 'itensIndex'])->name('index');
                    Route::get('/novo', [TabelaPrecoController::class, 'createItem'])->name('create');
                    Route::post('/', [TabelaPrecoController::class, 'storeItem'])->name('store');
                    Route::put('/{item}', [TabelaPrecoController::class, 'updateItem'])->name('update');
                    Route::delete('/{item}', [TabelaPrecoController::class, 'destroyItem'])->name('destroy');
                });
            });

            //Esocial
            Route::prefix('esocial/faixas')->name('esocial.faixas.')->group(function () {
                Route::get('/', [EsocialFaixaController::class, 'indexJson'])
                    ->name('json');
                Route::post('/', [EsocialFaixaController::class, 'store'])
                    ->name('store');
                Route::put('/{faixa}', [EsocialFaixaController::class, 'update'])
                    ->name('update');
                Route::delete('/{faixa}', [EsocialFaixaController::class, 'destroy'])
                    ->name('destroy');
            });

            //Treinamento NRs
            Route::name('treinamentos-nrs.')->group(function () {
                Route::get('/treinamentos-nrs.json', [ComercialTreinamentoNrController::class, 'indexJson'])
                    ->name('json');
                Route::post('/treinamentos-nrs', [ComercialTreinamentoNrController::class, 'store'])
                    ->name('store');
                Route::put('/treinamentos-nrs/{nr}', [ComercialTreinamentoNrController::class, 'update'])
                    ->name('update');
                Route::delete('/treinamentos-nrs/{nr}', [ComercialTreinamentoNrController::class, 'destroy'])
                    ->name('destroy');
            });

            //Exames
            Route::prefix('exames')->name('exames.')->group(function () {
                Route::get('/', [ExamesTabPrecoController::class, 'indexJson'])
                    ->name('indexJson');
                Route::post('/', [ExamesTabPrecoController::class, 'store'])
                    ->name('store');
                Route::put('/{exame}', [ExamesTabPrecoController::class, 'update'])
                    ->name('update');
                Route::delete('/{exame}', [ExamesTabPrecoController::class, 'destroy'])
                    ->name('destroy');
            });

            // Medições (LTCAT/LTIP)
            Route::prefix('medicoes')->name('medicoes.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Comercial\MedicoesTabPrecoController::class, 'indexJson'])
                    ->name('indexJson');
                Route::post('/', [\App\Http\Controllers\Comercial\MedicoesTabPrecoController::class, 'store'])
                    ->name('store');
                Route::put('/{medicao}', [\App\Http\Controllers\Comercial\MedicoesTabPrecoController::class, 'update'])
                    ->name('update');
                Route::delete('/{medicao}', [\App\Http\Controllers\Comercial\MedicoesTabPrecoController::class, 'destroy'])
                    ->name('destroy');
            });

            // Protocolos de Exames
            Route::prefix('protocolos-exames')->name('protocolos-exames.')->group(function () {
                Route::get('/', [ProtocolosExamesController::class, 'indexJson'])
                    ->name('indexJson');
                Route::post('/', [ProtocolosExamesController::class, 'store'])
                    ->name('store');
                Route::put('/{protocolo}', [ProtocolosExamesController::class, 'update'])
                    ->name('update');
                Route::delete('/{protocolo}', [ProtocolosExamesController::class, 'destroy'])
                    ->name('destroy');
            });

            // GHE do Cliente
            Route::prefix('clientes-ghes')->name('clientes-ghes.')->group(function () {
                Route::get('/', [ClienteGheController::class, 'indexJson'])
                    ->name('indexJson');
                Route::post('/', [ClienteGheController::class, 'store'])
                    ->name('store');
                Route::put('/{ghe}', [ClienteGheController::class, 'update'])
                    ->name('update');
                Route::delete('/{ghe}', [ClienteGheController::class, 'destroy'])
                    ->name('destroy');
            });

            // Minhas Comissões (vendedor)
            Route::prefix('minhas-comissoes')
                ->name('comissoes.')
                ->group(function () {
                    Route::get('/', [\App\Http\Controllers\Comercial\MinhasComissoesController::class, 'index'])
                        ->name('index');
                    Route::get('/{ano}', [\App\Http\Controllers\Comercial\MinhasComissoesController::class, 'index'])
                        ->whereNumber('ano')
                        ->name('ano');
                    Route::get('/{ano}/{mes}', [\App\Http\Controllers\Comercial\MinhasComissoesController::class, 'mes'])
                        ->whereNumber('ano')->whereNumber('mes')
                        ->name('mes');
                    Route::get('/{ano}/{mes}/previsao', [\App\Http\Controllers\Comercial\MinhasComissoesController::class, 'previsao'])
                        ->whereNumber('ano')->whereNumber('mes')
                        ->name('previsao');
                    Route::get('/{ano}/{mes}/efetivada', [\App\Http\Controllers\Comercial\MinhasComissoesController::class, 'efetivada'])
                        ->whereNumber('ano')->whereNumber('mes')
                        ->name('efetivada');
                    Route::get('/{ano}/{mes}/inadimplentes', [\App\Http\Controllers\Comercial\MinhasComissoesController::class, 'inadimplentes'])
                        ->whereNumber('ano')->whereNumber('mes')
                        ->name('inadimplentes');
                });

            // Agenda Comercial
            Route::prefix('agenda')->name('agenda.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Comercial\AgendaController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Comercial\AgendaController::class, 'store'])->name('store');
            Route::put('/{tarefa}', [\App\Http\Controllers\Comercial\AgendaController::class, 'update'])->name('update');
            Route::patch('/{tarefa}/concluir', [\App\Http\Controllers\Comercial\AgendaController::class, 'concluir'])->name('concluir');
            Route::delete('/{tarefa}', [\App\Http\Controllers\Comercial\AgendaController::class, 'destroy'])->name('destroy');
        });
        });

    // Financeiro (somente view/UI)
    Route::middleware(['auth'])
        ->prefix('financeiro')
        ->name('financeiro.')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Financeiro\DashboardController::class, 'index'])
                ->name('dashboard');
            Route::get('/faturamento-detalhado', [\App\Http\Controllers\Financeiro\FaturamentoDetalhadoController::class, 'index'])
                ->name('faturamento-detalhado');
            Route::get('/faturamento-detalhado/exportar-pdf', [\App\Http\Controllers\Financeiro\FaturamentoDetalhadoController::class, 'exportarPdf'])
                ->name('faturamento-detalhado.exportar-pdf');
            Route::get('/faturamento-detalhado/exportar-excel', [\App\Http\Controllers\Financeiro\FaturamentoDetalhadoController::class, 'exportarExcel'])
                ->name('faturamento-detalhado.exportar-excel');
            Route::get('/contratos', [\App\Http\Controllers\Financeiro\ContratoController::class, 'index'])
                ->name('contratos');
            Route::get('/contratos/{contrato}', [\App\Http\Controllers\Financeiro\ContratoController::class, 'show'])
                ->name('contratos.show');
            Route::get('/contas-receber', [\App\Http\Controllers\Financeiro\ContasReceberController::class, 'index'])
                ->name('contas-receber');
            Route::post('/contas-receber/itens', [\App\Http\Controllers\Financeiro\ContasReceberController::class, 'itens'])
                ->name('contas-receber.itens');
            Route::post('/contas-receber', [\App\Http\Controllers\Financeiro\ContasReceberController::class, 'store'])
                ->name('contas-receber.store');
            Route::get('/contas-receber/{contaReceber}', [\App\Http\Controllers\Financeiro\ContasReceberController::class, 'show'])
                ->name('contas-receber.show');
            Route::post('/contas-receber/{contaReceber}/baixar', [\App\Http\Controllers\Financeiro\ContasReceberController::class, 'baixar'])
                ->name('contas-receber.baixar');
            Route::post('/contas-receber/{contaReceber}/reabrir', [\App\Http\Controllers\Financeiro\ContasReceberController::class, 'reabrir'])
                ->name('contas-receber.reabrir');
            Route::post('/contas-receber/{contaReceber}/boleto', [\App\Http\Controllers\Financeiro\ContasReceberController::class, 'emitirBoleto'])
                ->name('contas-receber.boleto');
            Route::post('/contas-receber/{contaReceber}/itens', [\App\Http\Controllers\Financeiro\ContasReceberController::class, 'storeItem'])
                ->name('contas-receber.itens.store');
        });


    // ======================================================
    //                  CLIENTES (CRUD)
    // ======================================================
    Route::prefix('master/clientes')->name('clientes.')->group(function () {
        Route::get('/',               [ClienteController::class, 'index'])->name('index');
        Route::get('/create',         [ClienteController::class, 'create'])->name('create');
        Route::post('/',              [ClienteController::class, 'store'])->name('store');
        Route::get('/{cliente}',      [ClienteController::class, 'show'])->name('show');
        Route::get('/{cliente}/edit', [ClienteController::class, 'edit'])->name('edit');
        Route::put('/{cliente}',      [ClienteController::class, 'update'])->name('update');
        Route::delete('/{cliente}',   [ClienteController::class, 'destroy'])->name('destroy');
        Route::get('/{cliente}/acesso', [ClienteController::class, 'acessoForm'])->name('acesso.form');
        Route::post('/{cliente}/acesso', [ClienteController::class, 'criarAcesso'])->name('acesso');

        // 👉 NOVA ROTA: selecionar cliente para o portal
        Route::get('/{cliente}/portal', [ClienteController::class, 'selecionarParaPortal'])
            ->name('portal');

        // Consulta CNPJ
        Route::get('/consulta-cnpj/{cnpj}', [ClienteController::class, 'consultaCnpj'])
            ->name('consulta-cnpj');
        Route::get('/cnpj-exists/{cnpj}', [ClienteController::class, 'cnpjExists'])
            ->name('cnpj-exists');
    });

    // Cidades por UF
    Route::get('master/estados/{uf}/cidades', [ClienteController::class, 'cidadesPorUf'])
        ->name('estados.cidades');

    // ======================================================
    //                     MASTER
    // ======================================================
        Route::prefix('master')->name('master.')->group(function () {

        // Dashboard Master
        Route::get('/', [DashboardMaster::class, 'index'])->name('dashboard');
        Route::get('/dashboard-preferences', [DashboardPreferenceController::class, 'show'])
            ->name('dashboard-preferences.show');
        Route::put('/dashboard-preferences', [DashboardPreferenceController::class, 'update'])
            ->name('dashboard-preferences.update');
        Route::get('/agendamentos', [DashboardMaster::class, 'agendamentos'])->name('agendamentos');
        Route::get('/relatorio-tarefas', [DashboardMaster::class, 'relatorioTarefas'])
            ->name('relatorio-tarefas');
        Route::get('/relatorio-tarefas/pdf', [DashboardMaster::class, 'relatorioTarefasPdf'])
            ->name('relatorio-tarefas.pdf');
        Route::get('/relatorio-produtividade', [DashboardMaster::class, 'relatorioProdutividade'])
            ->name('relatorio-produtividade');
        Route::get('/relatorio-produtividade/pdf', [DashboardMaster::class, 'relatorioProdutividadePdf'])
            ->name('relatorio-produtividade.pdf');
        Route::get('/relatorios', [DashboardMaster::class, 'relatorios'])
            ->name('relatorios');
        Route::get('/relatorios/pdf', [DashboardMaster::class, 'relatoriosPdf'])
            ->name('relatorios.pdf');

        // Agenda de Vendedores
        Route::get('/agenda-vendedores', [AgendaVendedorController::class, 'index'])
            ->name('agenda-vendedores.index');
        Route::post('/agenda-vendedores', [AgendaVendedorController::class, 'store'])
            ->name('agenda-vendedores.store');
        Route::put('/agenda-vendedores/{tarefa}', [AgendaVendedorController::class, 'update'])
            ->name('agenda-vendedores.update');
        Route::patch('/agenda-vendedores/{tarefa}/concluir', [AgendaVendedorController::class, 'concluir'])
            ->name('agenda-vendedores.concluir');
        Route::delete('/agenda-vendedores/{tarefa}', [AgendaVendedorController::class, 'destroy'])
            ->name('agenda-vendedores.destroy');

        // Empresa (dados cadastrais)
        Route::prefix('empresa')->name('empresa.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Master\EmpresaController::class, 'edit'])
                ->name('edit');
            Route::put('/', [\App\Http\Controllers\Master\EmpresaController::class, 'update'])
                ->name('update');

            Route::prefix('unidades')->name('unidades.')->group(function () {
                Route::post('/', [\App\Http\Controllers\Master\UnidadeClinicaController::class, 'store'])
                    ->name('store');
                Route::get('/novo', [\App\Http\Controllers\Master\UnidadeClinicaController::class, 'create'])
                    ->name('create');
                Route::get('/{unidade}/editar', [\App\Http\Controllers\Master\UnidadeClinicaController::class, 'edit'])
                    ->name('edit');
                Route::put('/{unidade}', [\App\Http\Controllers\Master\UnidadeClinicaController::class, 'update'])
                    ->name('update');
                Route::delete('/{unidade}', [\App\Http\Controllers\Master\UnidadeClinicaController::class, 'destroy'])
                    ->name('destroy');
            });
        });

        // Caixas de Email (SMTP)
        Route::prefix('email-caixas')->name('email-caixas.')->group(function () {
            Route::get('/', [EmailCaixaController::class, 'index'])->name('index');
            Route::post('/', [EmailCaixaController::class, 'store'])->name('store');
            Route::post('/testar', [EmailCaixaController::class, 'testar'])->name('testar');
            Route::post('/{emailCaixa}/testar', [EmailCaixaController::class, 'testarSalvo'])
                ->name('testar-salvo');
            Route::post('/{emailCaixa}/enviar-teste', [EmailCaixaController::class, 'enviarTesteSalvo'])
                ->name('enviar-teste');
            Route::put('/{emailCaixa}', [EmailCaixaController::class, 'update'])->name('update');
            Route::delete('/{emailCaixa}', [EmailCaixaController::class, 'destroy'])->name('destroy');
        });

        Route::post('/tempo-tarefas', [\App\Http\Controllers\Master\TempoTarefasController::class, 'store'])
            ->name('tempo-tarefas.store');

        // Acessos
        Route::get('/acessos', [AcessosController::class, 'index'])->name('acessos');

        // Papéis
        Route::resource('papeis', PapelController::class)
            ->parameters(['papeis' => 'papel'])
            ->only(['index','store','update','destroy']);

        Route::post('papeis/{papel}/permissoes', [PapelController::class, 'syncPermissoes'])
            ->name('papeis.permissoes.sync');

        // Permissões
        Route::resource('permissoes', PermissaoController::class)
            ->parameters(['permissoes' => 'permissao'])
            ->only(['index','store','update','destroy']);

        // Usuários
        Route::prefix('usuarios')->name('usuarios.')->group(function () {
            Route::post('/', [AcessosController::class, 'usuariosStore'])->name('store');
            Route::patch('/{user}', [AcessosController::class, 'usuariosUpdate'])->name('update');
            Route::delete('/{user}', [AcessosController::class, 'usuariosDestroy'])->name('destroy');
            Route::post('/{user}/toggle', [AcessosController::class, 'usuariosToggle'])->name('toggle');
            Route::post('/{user}/reset', [AcessosController::class, 'usuariosReset'])->name('reset');
            Route::post('/{user}/password', [AcessosController::class, 'usuariosSetPassword'])->name('password');
        });

        // CRUD de Funções
        Route::prefix('funcoes')->name('funcoes.')->group(function () {
            Route::get('/', [FuncaoController::class, 'index'])->name('index');
            Route::post('/', [FuncaoController::class, 'store'])->name('store');
            Route::put('/{funcao}', [FuncaoController::class, 'update'])->name('update');
            Route::delete('/{funcao}', [FuncaoController::class, 'destroy'])->name('destroy');
        });

        // Comissões (parametrização)
        Route::prefix('comissoes')->name('comissoes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Master\ComissaoController::class, 'index'])
                ->name('index');
            Route::post('/', [\App\Http\Controllers\Master\ComissaoController::class, 'store'])
                ->name('store');
            Route::post('/bulk', [\App\Http\Controllers\Master\ComissaoController::class, 'bulkUpdate'])
                ->name('bulk');
            Route::put('/{servicoComissao}', [\App\Http\Controllers\Master\ComissaoController::class, 'update'])
                ->name('update');
            Route::delete('/{servicoComissao}', [\App\Http\Controllers\Master\ComissaoController::class, 'destroy'])
                ->name('destroy');
            Route::get('/vendedores', [\App\Http\Controllers\Master\ComissoesVendedoresController::class, 'index'])
                ->name('vendedores');
        });

        // Tabela de Preços (master reutiliza o mesmo conteúdo da área comercial)
        Route::prefix('tabela-precos')->name('tabela-precos.')->group(function () {
            Route::get('/', [TabelaPrecoController::class, 'itensIndex'])->name('index');
            Route::post('/', [TabelaPrecoController::class, 'update'])->name('update');

            Route::get('/itens', [TabelaPrecoController::class, 'itensIndex'])->name('itens.index');
            Route::get('/itens/novo', [TabelaPrecoController::class, 'createItem'])->name('itens.create');
            Route::post('/itens', [TabelaPrecoController::class, 'storeItem'])->name('itens.store');
            Route::put('/itens/{item}', [TabelaPrecoController::class, 'updateItem'])->name('itens.update');
            Route::delete('/itens/{item}', [TabelaPrecoController::class, 'destroyItem'])->name('itens.destroy');
        });

        // eSocial (faixas)
        Route::prefix('esocial/faixas')->name('esocial.faixas.')->group(function () {
            Route::get('/', [EsocialFaixaController::class, 'indexJson'])
                ->name('json');
            Route::post('/', [EsocialFaixaController::class, 'store'])
                ->name('store');
            Route::put('/{faixa}', [EsocialFaixaController::class, 'update'])
                ->name('update');
            Route::delete('/{faixa}', [EsocialFaixaController::class, 'destroy'])
                ->name('destroy');
        });

        // Treinamentos NRs
        Route::name('treinamentos-nrs.')->group(function () {
            Route::get('/treinamentos-nrs.json', [ComercialTreinamentoNrController::class, 'indexJson'])
                ->name('json');
            Route::post('/treinamentos-nrs', [ComercialTreinamentoNrController::class, 'store'])
                ->name('store');
            Route::put('/treinamentos-nrs/{nr}', [ComercialTreinamentoNrController::class, 'update'])
                ->name('update');
            Route::delete('/treinamentos-nrs/{nr}', [ComercialTreinamentoNrController::class, 'destroy'])
                ->name('destroy');
        });

        // Exames
        Route::prefix('exames')->name('exames.')->group(function () {
            Route::get('/', [ExamesTabPrecoController::class, 'indexJson'])
                ->name('indexJson');
            Route::post('/', [ExamesTabPrecoController::class, 'store'])
                ->name('store');
            Route::put('/{exame}', [ExamesTabPrecoController::class, 'update'])
                ->name('update');
            Route::delete('/{exame}', [ExamesTabPrecoController::class, 'destroy'])
                ->name('destroy');
        });

        // Medições (LTCAT/LTIP)
        Route::prefix('medicoes')->name('medicoes.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Comercial\MedicoesTabPrecoController::class, 'indexJson'])
                ->name('indexJson');
            Route::post('/', [\App\Http\Controllers\Comercial\MedicoesTabPrecoController::class, 'store'])
                ->name('store');
            Route::put('/{medicao}', [\App\Http\Controllers\Comercial\MedicoesTabPrecoController::class, 'update'])
                ->name('update');
            Route::delete('/{medicao}', [\App\Http\Controllers\Comercial\MedicoesTabPrecoController::class, 'destroy'])
                ->name('destroy');
        });

        // Protocolos de Exames
        Route::prefix('protocolos-exames')->name('protocolos-exames.')->group(function () {
            Route::get('/', [ProtocolosExamesController::class, 'indexJson'])
                ->name('indexJson');
            Route::post('/', [ProtocolosExamesController::class, 'store'])
                ->name('store');
            Route::put('/{protocolo}', [ProtocolosExamesController::class, 'update'])
                ->name('update');
            Route::delete('/{protocolo}', [ProtocolosExamesController::class, 'destroy'])
                ->name('destroy');
        });

        // GHE do Cliente
        Route::prefix('clientes-ghes')->name('clientes-ghes.')->group(function () {
            Route::get('/', [ClienteGheController::class, 'indexJson'])
                ->name('indexJson');
            Route::post('/', [ClienteGheController::class, 'store'])
                ->name('store');
            Route::put('/{ghe}', [ClienteGheController::class, 'update'])
                ->name('update');
            Route::delete('/{ghe}', [ClienteGheController::class, 'destroy'])
                ->name('destroy');
        });
    });

    // ======================================================
    //                TABELA DE PREÇOS
    // ======================================================
//    Route::prefix('master/tabela-precos')->name('tabela-precos.')->group(function () {
//        Route::get('/', [TabelaPrecoController::class,'index'])->name('index');
//
//        Route::post('/itens',                 [TabelaPrecoItemController::class,'store'])->name('items.store');
//        Route::put('/itens/{item}',           [TabelaPrecoItemController::class,'update'])->name('items.update');
//        Route::patch('/itens/{item}/toggle',  [TabelaPrecoItemController::class,'toggle'])->name('items.toggle');
//        Route::delete('/itens/{item}',        [TabelaPrecoItemController::class,'destroy'])->name('items.destroy');
//    });

    // ======================================================
    //                    APIs internas
    // ======================================================
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/clientes', [ClientesApiController::class, 'index'])->name('clientes.index');
        Route::get('/servicos', [ServicosApiController::class, 'index'])->name('servicos.index');
    });
});

Route::get('operacional/tarefas/documento/{token}', [TarefaController::class, 'downloadDocumento'])
    ->name('operacional.tarefas.documento');

require __DIR__.'/auth.php';
