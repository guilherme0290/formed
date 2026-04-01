<?php

namespace App\Http\Controllers;

use App\Helpers\S3Helper;
use App\Models\Anexos;
use App\Models\AsoSolicitacoes;
use App\Models\ClienteContrato;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Services\AsoGheService;
use App\Services\ComissaoService;
use App\Services\FuncionarioArquivosZipService;
use App\Services\PrecificacaoService;
use App\Services\VendaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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

        return view('tarefas.form', ['tarefa' => new Tarefa, 'colunas' => $colunas]);
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

        return view('tarefas.form', compact('tarefa', 'colunas'));
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
        $maxUploadMb = $this->resolveTaskUploadLimitMb($tarefa);

        $data = $request->validate(
            [
                'arquivo_cliente' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$this->mbToKilobytes($maxUploadMb)],
                'notificar' => ['nullable', 'boolean'],
            ],
            $this->buildArquivoClienteMessages($maxUploadMb)
        );
        $path = S3Helper::upload($request->file('arquivo_cliente'), 'tarefas');

        $tarefa->update([
            'path_documento_cliente' => $path,
            'documento_token' => Tarefa::gerarDocumentoTokenCurto(),
        ]);

        return $this->finalizarTarefaPersistida($tarefa, $precificacaoService, $vendaService, $comissaoService);
    }

    public function finalizarComDocumentoExistente(Tarefa $tarefa, PrecificacaoService $precificacaoService, VendaService $vendaService, ComissaoService $comissaoService)
    {
        $pendenciaCertificados = $this->resolverPendenciaCertificadosTreinamento($tarefa);
        $permiteSemDocumento = $pendenciaCertificados['requer_certificados']
            && ! ($pendenciaCertificados['exige_documento_base'] ?? false);

        if (blank($tarefa->path_documento_cliente) && ! $permiteSemDocumento) {
            return response()->json([
                'ok' => false,
                'error' => 'Anexe primeiro o documento final da tarefa antes de finalizar.',
            ], 422);
        }

        return $this->finalizarTarefaPersistida($tarefa, $precificacaoService, $vendaService, $comissaoService);
    }

    public function downloadDocumento(string $token)
    {
        $tarefa = Tarefa::where('documento_token', $token)
            ->whereNotNull('path_documento_cliente')
            ->firstOrFail();

        return $this->responderArquivoPorPath(
            (string) $tarefa->path_documento_cliente,
            $this->nomeArquivoDocumentoCompartilhado($tarefa),
            'application/pdf'
        );
    }

    public function downloadPacotePublico(Request $request, Tarefa $tarefa, FuncionarioArquivosZipService $zipService)
    {
        abort_unless($request->hasValidSignature(), 403);

        try {
            $zipPath = $zipService->gerarZipPorIds($tarefa->cliente, [$tarefa->id], null, true);
        } catch (\RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        return response()
            ->download($zipPath, 'tarefa-'.$tarefa->id.'-arquivos.zip')
            ->deleteFileAfterSend(true);
    }

    public function downloadPacotePublicoPorToken(string $token, FuncionarioArquivosZipService $zipService)
    {
        $tarefa = Tarefa::where('documento_token', $token)
            ->firstOrFail();

        try {
            $zipPath = $zipService->gerarZipPorIds($tarefa->cliente, [$tarefa->id], null, true);
        } catch (\RuntimeException $e) {
            abort(404, $e->getMessage());
        }

        return response()
            ->download($zipPath, 'tarefa-'.$tarefa->id.'-arquivos.zip')
            ->deleteFileAfterSend(true);
    }

    public function substituirDocumentoCliente(Request $request, Tarefa $tarefa)
    {
        $maxUploadMb = $this->resolveTaskUploadLimitMb($tarefa);

        $request->validate(
            [
                'arquivo_cliente' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$this->mbToKilobytes($maxUploadMb)],
            ],
            $this->buildArquivoClienteMessages($maxUploadMb)
        );

        $path = S3Helper::upload($request->file('arquivo_cliente'), 'tarefas');

        $tarefa->update([
            'path_documento_cliente' => $path,
            'documento_token' => Tarefa::gerarDocumentoTokenCurto(),
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

    public function removerDocumentoCliente(Tarefa $tarefa, Request $request)
    {
        abort_if((int) $tarefa->empresa_id !== (int) (auth()->user()->empresa_id ?? 0), 403);

        if ($tarefa->path_documento_cliente && S3Helper::exists($tarefa->path_documento_cliente)) {
            S3Helper::delete($tarefa->path_documento_cliente);
        }

        $tarefa->update([
            'path_documento_cliente' => null,
            'documento_token' => null,
        ]);

        TarefaLog::create([
            'tarefa_id' => $tarefa->id,
            'user_id' => Auth::id(),
            'de_coluna_id' => $tarefa->coluna_id,
            'para_coluna_id' => $tarefa->coluna_id,
            'acao' => 'documento',
            'observacao' => 'Documento principal da tarefa removido',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Documento removido com sucesso.',
            ]);
        }

        return back()->with('ok', 'Documento removido com sucesso.');
    }

    public function substituirDocumentoComplementar(Request $request, Tarefa $tarefa)
    {
        if (! (bool) optional($tarefa->pgr)->com_pcms0) {
            return response()->json([
                'ok' => false,
                'error' => 'Esta tarefa não exige documento complementar.',
            ], 422);
        }

        return $this->substituirDocumentoAdicional(
            request: $request,
            tarefa: $tarefa,
            servico: 'documento_complementar_pgr_pcmso',
            pasta: 'tarefas-complementares',
            observacao: 'Documento complementar do PGR + PCMSO anexado'
        );
    }

    public function substituirDocumentoArt(Request $request, Tarefa $tarefa)
    {
        if (! (bool) optional($tarefa->pgr)->com_art) {
            return response()->json([
                'ok' => false,
                'error' => 'Esta tarefa não exige ART.',
            ], 422);
        }

        return $this->substituirDocumentoAdicional(
            request: $request,
            tarefa: $tarefa,
            servico: 'documento_art_pgr_pcmso',
            pasta: 'tarefas-art',
            observacao: 'Documento ART do PGR + PCMSO anexado'
        );
    }

    public function uploadCertificadosTreinamento(Request $request, Tarefa $tarefa)
    {
        $request->validate([
            'arquivos' => ['required', 'array', 'min:1'],
            'arquivos.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $empresaId = (int) (auth()->user()->empresa_id ?? 0);
        abort_if($empresaId <= 0 || (int) $tarefa->empresa_id !== $empresaId, 403);

        $pendenciaInicial = $this->resolverPendenciaCertificadosTreinamento($tarefa);
        if (! $pendenciaInicial['requer_certificados']) {
            return response()->json([
                'ok' => false,
                'error' => 'Esta tarefa não possui pendência de certificados de treinamento.',
            ], 422);
        }

        $anexosCriados = [];

        foreach ($request->file('arquivos', []) as $file) {
            $path = S3Helper::upload($file, 'anexos/'.$tarefa->id.'/certificados-treinamento');

            $anexo = Anexos::create([
                'empresa_id' => $tarefa->empresa_id,
                'cliente_id' => $tarefa->cliente_id,
                'tarefa_id' => $tarefa->id,
                'uploaded_by' => (int) auth()->id(),
                'servico' => 'certificado_treinamento',
                'nome_original' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'tamanho' => $file->getSize(),
            ]);

            $anexosCriados[] = [
                'id' => $anexo->id,
                'servico' => $anexo->servico,
                'url' => $anexo->fresh()->url,
                'delete_url' => route('operacional.anexos.destroy', $anexo),
                'mime' => $anexo->mime_type,
                'uploaded_by' => optional(auth()->user())->name ?? 'Sistema',
                'tamanho' => $anexo->tamanho,
            ];
        }

        $pendenciaAtual = $this->resolverPendenciaCertificadosTreinamento($tarefa->fresh());

        return response()->json([
            'ok' => true,
            'certificados' => $pendenciaAtual,
            'status_label' => $tarefa->fresh()->coluna?->nome,
            'anexos' => $anexosCriados,
        ]);
    }

    private function resolverPendenciaCertificadosTreinamento(Tarefa $tarefa): array
    {
        $aso = AsoSolicitacoes::query()
            ->where('tarefa_id', $tarefa->id)
            ->first();

        $codigos = [];
        $origem = null;
        $exigeDocumentoBase = false;

        if ($aso && (bool) $aso->vai_fazer_treinamento) {
            $codigos = (array) ($aso->treinamentos ?? []);
            if (empty($codigos) && is_array($aso->treinamento_pacote) && ! empty($aso->treinamento_pacote['codigos'])) {
                $codigos = (array) $aso->treinamento_pacote['codigos'];
            }
            $origem = 'aso';
            $exigeDocumentoBase = true;
        } else {
            $treinamentoDetalhes = $tarefa->relationLoaded('treinamentoNrDetalhes')
                ? $tarefa->treinamentoNrDetalhes
                : $tarefa->treinamentoNrDetalhes()->first();

            if ($treinamentoDetalhes) {
                $payload = (array) ($treinamentoDetalhes->treinamentos ?? []);
                $modo = (string) ($payload['modo'] ?? '');

                if ($modo === 'pacote') {
                    $codigos = (array) data_get($payload, 'pacote.codigos', []);
                } elseif (array_key_exists('codigos', $payload)) {
                    $codigos = (array) ($payload['codigos'] ?? []);
                } else {
                    $codigos = (array) $payload;
                }

                $origem = 'treinamento_nr';
            }
        }

        if ($origem === null) {
            return [
                'requer_certificados' => false,
                'total_esperado' => 0,
                'enviados' => 0,
                'pendente' => false,
                'origem' => null,
                'exige_documento_base' => false,
            ];
        }

        $codigos = array_values(array_unique(array_filter(array_map(
            static fn ($v) => trim((string) $v),
            $codigos
        ))));

        $totalEsperado = count($codigos);
        if ($totalEsperado === 0) {
            $totalEsperado = 1;
        }

        $certificadosEnviados = Anexos::query()
            ->where('tarefa_id', $tarefa->id)
            ->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['certificado_treinamento'])
            ->count();

        return [
            'requer_certificados' => true,
            'total_esperado' => $totalEsperado,
            'enviados' => $certificadosEnviados,
            'pendente' => $certificadosEnviados < $totalEsperado,
            'origem' => $origem,
            'exige_documento_base' => $exigeDocumentoBase,
        ];
    }

    private function finalizarTarefaPersistida(Tarefa $tarefa, PrecificacaoService $precificacaoService, VendaService $vendaService, ComissaoService $comissaoService)
    {
        $pendenciaDocumentosCombinados = $this->resolverPendenciaDocumentosCombinados($tarefa);
        if ($pendenciaDocumentosCombinados['pendente']) {
            return response()->json([
                'ok' => false,
                'error' => $pendenciaDocumentosCombinados['message'],
            ], 422);
        }

        $colunaFinalizada = KanbanColuna::where('empresa_id', $tarefa->empresa_id)
            ->where('slug', 'finalizada')
            ->firstOrFail();
        $colunaAguardandoFornecedor = KanbanColuna::where('empresa_id', $tarefa->empresa_id)
            ->where('slug', 'aguardando-fornecedor')
            ->first()
            ?? KanbanColuna::where('empresa_id', $tarefa->empresa_id)
                ->where('slug', 'aguardando')
                ->first();

        $colunaAtualId = (int) $tarefa->coluna_id;
        $pendenciaCertificados = $this->resolverPendenciaCertificadosTreinamento($tarefa->fresh());
        $moverParaAguardandoFornecedor = $pendenciaCertificados['pendente'] && $colunaAguardandoFornecedor;
        $colunaDestino = $moverParaAguardandoFornecedor ? $colunaAguardandoFornecedor : $colunaFinalizada;
        $mensagemRetorno = $moverParaAguardandoFornecedor
            ? sprintf(
                'A tarefa foi movida para Aguardando fornecedor. Ela ainda não pode ser concluída porque espera %d certificado(s) e recebeu %d.',
                (int) ($pendenciaCertificados['total_esperado'] ?? 0),
                (int) ($pendenciaCertificados['enviados'] ?? 0)
            )
            : 'Tarefa finalizada com sucesso.';

        DB::beginTransaction();

        try {
            if (! $moverParaAguardandoFornecedor) {
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
                    $servicoPcmsoId = (int) Servico::where('empresa_id', $tarefa->empresa_id)
                        ->where('nome', 'PCMSO')
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
                    } elseif ($servicoPcmsoId && (int) $tarefa->servico_id === $servicoPcmsoId) {
                        $resultado = $precificacaoService->precificarPcmso($tarefa);
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
                } catch (Throwable $e) {
                    $mensagem = 'Não é possível concluir esta tarefa porque o cliente não possui preço definido para este serviço na proposta/contrato ativo. Solicite ao Comercial para ajustar a proposta e fechar novamente, ou cadastrar o valor do serviço no contrato do cliente.';
                    if (method_exists($e, 'errors')) {
                        $mensagem = collect($e->errors())->flatten()->first() ?? $mensagem;
                    } elseif (filled($e->getMessage())) {
                        $mensagem = $e->getMessage();
                    }

                    DB::rollBack();

                    return response()->json([
                        'ok' => false,
                        'error' => $mensagem,
                    ], 422);
                }
            }

            $tarefa->update([
                'coluna_id' => $colunaDestino->id,
                'finalizado_em' => $moverParaAguardandoFornecedor ? null : now(),
            ]);

            $log = TarefaLog::create([
                'tarefa_id' => $tarefa->id,
                'user_id' => Auth::id(),
                'de_coluna_id' => $colunaAtualId,
                'para_coluna_id' => $colunaDestino->id,
                'acao' => 'movido',
                'observacao' => $moverParaAguardandoFornecedor
                    ? (($pendenciaCertificados['origem'] ?? null) === 'treinamento_nr'
                        ? 'Treinamento aguardando certificados.'
                        : 'ASO com documento anexado. Aguardando certificados de treinamento.')
                    : (($pendenciaCertificados['origem'] ?? null) === 'treinamento_nr'
                        ? 'Treinamento finalizado com certificados anexados.'
                        : 'Finalizada com documento anexado'),
            ]);

            $log->load(['deColuna', 'paraColuna', 'user']);
            DB::commit();

            return response()->json([
                'ok' => true,
                'status_label' => $colunaDestino->nome,
                'documento_url' => $tarefa->fresh()->documento_link,
                'coluna_destino_slug' => $colunaDestino->slug,
                'finalizada_total' => ! $moverParaAguardandoFornecedor,
                'certificados' => $pendenciaCertificados,
                'message' => $mensagemRetorno,
                'log' => [
                    'de' => optional($log->deColuna)->nome ?? 'Início',
                    'para' => optional($log->paraColuna)->nome ?? '-',
                    'user' => optional($log->user)->name ?? 'Sistema',
                    'data' => optional($log->created_at)->format('d/m H:i'),
                ],
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'error' => filled($e->getMessage()) ? $e->getMessage() : 'Erro ao finalizar a tarefa.',
            ], 500);
        }
    }

    private function resolverPendenciaDocumentosCombinados(Tarefa $tarefa): array
    {
        $pgr = $tarefa->pgr;
        $requerDoisDocumentos = (bool) ($pgr?->com_pcms0);

        if (! $requerDoisDocumentos) {
            return [
                'pendente' => false,
                'message' => null,
            ];
        }

        $temDocumentoPrincipal = filled($tarefa->path_documento_cliente);
        $temDocumentoComplementar = Anexos::query()
            ->where('tarefa_id', $tarefa->id)
            ->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['documento_complementar_pgr_pcmso'])
            ->exists();
        $requerArt = (bool) ($pgr?->com_art);
        $temDocumentoArt = ! $requerArt || Anexos::query()
            ->where('tarefa_id', $tarefa->id)
            ->whereRaw('LOWER(COALESCE(servico, "")) = ?', ['documento_art_pgr_pcmso'])
            ->exists();

        if ($temDocumentoPrincipal && $temDocumentoComplementar && $temDocumentoArt) {
            return [
                'pendente' => false,
                'message' => null,
            ];
        }

        return [
            'pendente' => true,
            'message' => $requerArt
                ? 'Para finalizar PGR + PCMSO com ART, anexe os documentos finais de PGR, PCMSO e ART.'
                : 'Para finalizar PGR + PCMSO, anexe os dois documentos finais: PGR e PCMSO.',
        ];
    }

    private function substituirDocumentoAdicional(Request $request, Tarefa $tarefa, string $servico, string $pasta, string $observacao)
    {
        $maxUploadMb = $this->resolveAdditionalDocumentUploadLimitMb($tarefa, $servico);

        $request->validate(
            [
                'arquivo_cliente' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$this->mbToKilobytes($maxUploadMb)],
            ],
            $this->buildArquivoClienteMessages($maxUploadMb)
        );

        $anexoExistente = Anexos::query()
            ->where('tarefa_id', $tarefa->id)
            ->whereRaw('LOWER(COALESCE(servico, "")) = ?', [mb_strtolower($servico)])
            ->latest('id')
            ->first();

        $path = S3Helper::upload($request->file('arquivo_cliente'), $pasta);

        $payload = [
            'empresa_id' => $tarefa->empresa_id,
            'cliente_id' => $tarefa->cliente_id,
            'tarefa_id' => $tarefa->id,
            'uploaded_by' => (int) Auth::id(),
            'servico' => $servico,
            'nome_original' => $request->file('arquivo_cliente')->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $request->file('arquivo_cliente')->getClientMimeType(),
            'tamanho' => $request->file('arquivo_cliente')->getSize(),
        ];

        if ($anexoExistente) {
            $anexoExistente->update($payload);
        } else {
            $anexoExistente = Anexos::create($payload);
        }

        TarefaLog::create([
            'tarefa_id' => $tarefa->id,
            'user_id' => Auth::id(),
            'de_coluna_id' => $tarefa->coluna_id,
            'para_coluna_id' => $tarefa->coluna_id,
            'acao' => 'documento',
            'observacao' => $observacao,
        ]);

        return response()->json([
            'ok' => true,
            'documento_url' => $anexoExistente->fresh()->url,
            'delete_url' => route('operacional.anexos.destroy', $anexoExistente),
            'anexo' => [
                'id' => $anexoExistente->id,
                'servico' => $anexoExistente->servico,
                'url' => $anexoExistente->fresh()->url,
                'delete_url' => route('operacional.anexos.destroy', $anexoExistente),
                'mime' => $anexoExistente->mime_type,
                'uploaded_by' => optional(Auth::user())->name ?? 'Sistema',
            ],
        ]);
    }

    private function buildArquivoClienteMessages(int $maxUploadMb): array
    {
        return [
            'arquivo_cliente.required' => 'Selecione um arquivo do cliente.',
            'arquivo_cliente.file' => 'O arquivo do cliente deve ser um arquivo válido.',
            'arquivo_cliente.uploaded' => 'Não foi possível enviar o arquivo do cliente. Tente novamente.',
            'arquivo_cliente.mimes' => 'O arquivo do cliente deve ser do tipo: pdf, jpg, jpeg ou png.',
            'arquivo_cliente.max' => "O arquivo do cliente deve ter no máximo {$maxUploadMb} MB.",
        ];
    }

    private function resolveTaskUploadLimitMb(Tarefa $tarefa): int
    {
        $serviceName = $this->resolveTaskServiceName($tarefa);
        $limits = config('services.upload_limits', []);
        $defaultMb = max(1, (int) ($limits['default_mb'] ?? 10));

        return match ($serviceName) {
            'PGR' => max(1, (int) ($limits['pgr_mb'] ?? 100)),
            'PCMSO' => max(1, (int) ($limits['pcmso_mb'] ?? 100)),
            default => $defaultMb,
        };
    }

    private function resolveAdditionalDocumentUploadLimitMb(Tarefa $tarefa, string $servico): int
    {
        return match (mb_strtolower(trim($servico))) {
            'documento_complementar_pgr_pcmso' => max(1, (int) (config('services.upload_limits.pcmso_mb') ?? 100)),
            default => $this->resolveTaskUploadLimitMb($tarefa),
        };
    }

    private function resolveTaskServiceName(Tarefa $tarefa): string
    {
        $tarefa->loadMissing('servico:id,nome');

        return mb_strtoupper(trim((string) ($tarefa->servico?->nome ?? '')));
    }

    private function mbToKilobytes(int $megabytes): int
    {
        return max(1, $megabytes) * 1024;
    }

    private function responderArquivoPorPath(string $path, string $nomeArquivo, string $mimePadrao): StreamedResponse
    {
        abort_if(blank($path), 404);

        $disk = $this->resolverDiskParaPath($path);
        abort_if($disk === null, 404);

        $stream = Storage::disk($disk)->readStream($path);
        abort_if($stream === false, 404);

        $mime = (string) (Storage::disk($disk)->mimeType($path) ?: $mimePadrao);
        $disposition = str_starts_with($mime, 'image/') || $mime === 'application/pdf'
            ? 'inline'
            : 'attachment';

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.addslashes($nomeArquivo).'"',
        ]);
    }

    private function resolverDiskParaPath(string $path): ?string
    {
        foreach (['public', 's3'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return $disk;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function nomeArquivoDocumentoCompartilhado(Tarefa $tarefa): string
    {
        $servico = trim((string) optional($tarefa->servico)->nome);
        $servico = $servico !== '' ? Str::slug($servico) : 'documento';
        $ext = strtolower((string) pathinfo((string) $tarefa->path_documento_cliente, PATHINFO_EXTENSION));
        $ext = $ext !== '' ? $ext : 'pdf';

        return sprintf('tarefa-%d-%s.%s', (int) $tarefa->id, $servico, $ext);
    }
}
