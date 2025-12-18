<?php

namespace App\Http\Controllers;

use App\Helpers\S3Helper;
use App\Models\TarefaLog;
use Illuminate\Http\Request;
use App\Models\Tarefa;
use App\Models\KanbanColuna;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\PrecificacaoService;
use App\Services\VendaService;

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


    public function finalizarComArquivo(Request $request, Tarefa $tarefa, PrecificacaoService $precificacaoService, VendaService $vendaService)
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
                $resultado = $precificacaoService->validarServicoNoContrato(
                    (int) $tarefa->cliente_id,
                    (int) $tarefa->servico_id,
                    (int) $tarefa->empresa_id
                );
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

            $path = S3Helper::upload( $request->file('arquivo_cliente'), 'tarefas');

            $tarefa->update([
                'coluna_id'               => $colunaFinalizada->id,
                'finalizado_em'           => now(),
                'path_documento_cliente'  => $path,
            ]);

            if (isset($resultado)) {
                $vendaService->criarVendaPorTarefa($tarefa, $resultado['contrato'], $resultado['item']);
            }

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

                // 🔁 URL já vinda do S3
                'arquivo_url'  => $path ? Storage::disk('s3')->temporaryUrl(
                    $path,
                    now()->addMinutes(10)
                ) : null,

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

}
