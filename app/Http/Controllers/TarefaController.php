<?php

namespace App\Http\Controllers;

use App\Helpers\S3Helper;
use App\Models\TarefaLog;
use Illuminate\Http\Request;
use App\Models\Tarefa;
use App\Models\KanbanColuna;
use App\Models\ClienteContrato;
use App\Models\Servico;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\PrecificacaoService;
use App\Services\VendaService;
use App\Services\ComissaoService;
use App\Services\AsoGheService;

class TarefaController extends Controller
{
    public function index()
    {
        $tarefas = Tarefa::latest()->paginate(20);
        return view('tarefas.index', compact('tarefas'));
    }

    public function create()
    {
        $colunas = KanbanColuna::orderBy('ordem')->get();
        return view('tarefas.form', ['tarefa'=>new Tarefa(), 'colunas'=>$colunas]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'coluna_id' => 'required|exists:kanban_colunas,id',
        ]);

        $data['empresa_id'] = auth()->user()->empresa_id ?? null;
        $data['responsavel_id'] = auth()->id();

        $tarefa = Tarefa::create($data);
        return redirect()->route('tarefas.show', $tarefa)->with('ok', 'Tarefa criada.');
    }

    public function show(Tarefa $tarefa)
    {
        return view('tarefas.show', compact('tarefa'));
    }

    public function edit(Tarefa $tarefa)
    {
        $colunas = KanbanColuna::orderBy('ordem')->get();
        return view('tarefas.form', compact('tarefa','colunas'));
    }

    public function update(Request $r, Tarefa $tarefa)
    {
        $data = $r->validate([
            'titulo' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'coluna_id' => 'required|exists:kanban_colunas,id',
        ]);

        $tarefa->update($data);
        return redirect()->route('tarefas.show', $tarefa)->with('ok', 'Tarefa atualizada.');
    }

    public function destroy(Tarefa $tarefa)
    {
        $tarefa->delete();
        return redirect()->route('tarefas.index')->with('ok', 'Tarefa removida.');
    }


    public function finalizarComArquivo(Request $request, Tarefa $tarefa, PrecificacaoService $precificacaoService, VendaService $vendaService, ComissaoService $comissaoService)
    {
        $data = $request->validate([
            'arquivo_cliente' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'notificar'       => ['nullable', 'boolean'],
        ]);

        // coluna "finalizada" (se usar slug)
        $colunaFinalizada = KanbanColuna::where('empresa_id', $tarefa->empresa_id)
            ->where('slug', 'finalizada')
            ->firstOrFail();

        $colunaAtualId = (int) $tarefa->coluna_id;

        DB::beginTransaction();

        try {

            try {
                $dataRef = now()->startOfDay();
                $contratoAtivo = ClienteContrato::query()
                    ->where('empresa_id', $tarefa->empresa_id)
                    ->where('cliente_id', $tarefa->cliente_id)
                    ->where('status', 'ATIVO')
                    ->where(function ($q) use ($dataRef) {
                        $q->whereNull('vigencia_inicio')->orWhere('vigencia_inicio', '<=', $dataRef);
                    })
                    ->where(function ($q) use ($dataRef) {
                        $q->whereNull('vigencia_fim')->orWhere('vigencia_fim', '>=', $dataRef);
                    })
                    ->with('itens')
                    ->latest('vigencia_inicio')
                    ->first();
                $asoServicoId = app(AsoGheService::class)->resolveServicoAsoIdFromContrato($contratoAtivo);
                $isAso = $asoServicoId && (int) $tarefa->servico_id === (int) $asoServicoId;

                $servicoTreinamentoId = (int) Servico::where('empresa_id', $tarefa->empresa_id)
                    ->where('nome', 'Treinamentos NRs')
                    ->value('id');
                $servicoPgrId = (int) Servico::where('empresa_id', $tarefa->empresa_id)
                    ->where('nome', 'PGR')
                    ->value('id');

                if ($isAso) {
                    $resultado = $precificacaoService->precificarAso($tarefa);
                    $venda = $vendaService->criarVendaPorTarefaItens($tarefa, $resultado['contrato'], $resultado['itensVenda']);

                    $vendedorId = optional($tarefa->cliente)->vendedor_id;
                    $comissaoService->gerarPorVenda($venda, $resultado['itemContrato'], $vendedorId ?: auth()->id());
                } elseif ($servicoTreinamentoId && (int) $tarefa->servico_id === $servicoTreinamentoId) {
                    $resultado = $precificacaoService->precificarTreinamentosNr($tarefa);
                    $venda = $vendaService->criarVendaPorTarefaItens($tarefa, $resultado['contrato'], $resultado['itensVenda']);

                    $vendedorId = optional($tarefa->cliente)->vendedor_id;
                    $comissaoService->gerarPorVenda($venda, $resultado['itemContrato'], $vendedorId ?: auth()->id());
                } elseif ($servicoPgrId && (int) $tarefa->servico_id === $servicoPgrId) {
                    $resultado = $precificacaoService->precificarPgr($tarefa);
                    $venda = $vendaService->criarVendaPorTarefaItens($tarefa, $resultado['contrato'], $resultado['itensVenda']);

                    $vendedorId = optional($tarefa->cliente)->vendedor_id;
                    $comissaoService->gerarPorVenda($venda, $resultado['itemContrato'], $vendedorId ?: auth()->id());
                } else {
                    $resultado = $precificacaoService->validarServicoNoContrato(
                        (int) $tarefa->cliente_id,
                        (int) $tarefa->servico_id,
                        (int) $tarefa->empresa_id
                    );
                    $venda = $vendaService->criarVendaPorTarefa($tarefa, $resultado['contrato'], $resultado['item']);

                    $vendedorId = optional($tarefa->cliente)->vendedor_id;
                    $comissaoService->gerarPorVenda($venda, $resultado['item'], $vendedorId ?: auth()->id());
                }

            } catch (\Throwable $e) {
                $mensagem = 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para este serviço na proposta/contrato ativo. Solicite ao Comercial para ajustar a proposta e fechar novamente, ou cadastrar o valor do serviço no contrato do cliente.';
                if (method_exists($e, 'errors')) {
                    $mensagem = collect($e->errors())->flatten()->first() ?? $mensagem;
                }
                return response()->json([
                    'ok' => false,
                    'error' => $mensagem,
                ], 422);
            }

            $path = S3Helper::upload($request->file('arquivo_cliente'), 'tarefas');

            $token = Str::uuid()->toString();
            while (Tarefa::where('documento_token', $token)->exists()) {
                $token = Str::uuid()->toString();
            }

            $tarefa->update([
                'coluna_id'               => $colunaFinalizada->id,
                'finalizado_em'           => now(),
                'path_documento_cliente'  => $path,
                'documento_token'         => $token,
            ]);

            $log = TarefaLog::create([
                'tarefa_id'      => $tarefa->id,
                'user_id'        => Auth::id(),
                'de_coluna_id'   => $colunaAtualId,
                'para_coluna_id' => $colunaFinalizada->id,
                'acao'           => 'movido',
                'observacao'     => 'Finalizada com arquivo anexado',
            ]);

            $log->load(['deColuna','paraColuna','user']);

            DB::commit();



            return response()->json([
                'ok'           => true,
                'status_label' => $colunaFinalizada->nome,
                'documento_url' => $tarefa->documento_link,
                'log'          => [
                    'de'   => optional($log->deColuna)->nome ?? 'Início',
                    'para' => optional($log->paraColuna)->nome ?? '-',
                    'user' => optional($log->user)->name ?? 'Sistema',
                    'data' => optional($log->created_at)->format('d/m H:i'),
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok'    => false,
                'error' => 'Erro ao finalizar a tarefa.',
            ], 500);
        }
    }

    public function downloadDocumento(string $token)
    {
        $tarefa = Tarefa::where('documento_token', $token)
            ->whereNotNull('path_documento_cliente')
            ->firstOrFail();

        $url = S3Helper::temporaryUrl($tarefa->path_documento_cliente, 10);

        return redirect()->away($url);
    }

    public function substituirDocumentoCliente(Request $request, Tarefa $tarefa)
    {
        $request->validate([
            'arquivo_cliente' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $path = S3Helper::upload($request->file('arquivo_cliente'), 'tarefas');

        $token = Str::uuid()->toString();
        while (Tarefa::where('documento_token', $token)->exists()) {
            $token = Str::uuid()->toString();
        }

        $tarefa->update([
            'path_documento_cliente' => $path,
            'documento_token' => $token,
        ]);

        TarefaLog::create([
            'tarefa_id' => $tarefa->id,
            'user_id' => Auth::id(),
            'de_coluna_id' => $tarefa->coluna_id,
            'para_coluna_id' => $tarefa->coluna_id,
            'acao' => 'documento',
            'observacao' => 'Documento do cliente substituído (temporário)',
        ]);

        return response()->json([
            'ok' => true,
            'documento_url' => $tarefa->documento_link,
        ]);
    }

}
