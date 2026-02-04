<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ClienteAsoGrupo;
use App\Models\ClienteGhe;
use App\Models\ClienteContratoItem;
use App\Models\ClienteContratoLog;
use App\Models\Empresa;
use App\Models\Funcao;
use App\Models\Ghe;
use App\Models\Proposta;
use App\Models\PropostaAsoGrupo;
use App\Models\PropostaItens;
use App\Models\ProtocoloExame;
use App\Models\Servico;
use App\Models\TabelaPrecoItem;
use App\Models\TabelaPrecoPadrao;
use App\Models\UnidadeClinica;
use App\Services\AsoGheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\PropostaService;
use Barryvdh\DomPDF\Facade\Pdf;

class PropostaController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id;
        $isMaster = $user->hasPapel('Master');

        $q = trim((string) $request->query('q', ''));
        $status = strtoupper(trim((string) $request->query('status', '')));

        $query = Proposta::query()
            ->with(['cliente', 'empresa'])
            ->where('empresa_id', $empresaId);

        if (!$isMaster) {
            $query->where('vendedor_id', $user->id);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                if (ctype_digit($q)) {
                    $sub->orWhere('id', (int) $q);
                }

                $sub->orWhere('codigo', 'like', '%' . $q . '%')
                    ->orWhere('status', 'like', '%' . $q . '%')
                    ->orWhereHas('cliente', function ($c) use ($q) {
                        $c->where('razao_social', 'like', '%' . $q . '%');
                    });
            });
        }

        if ($status !== '' && $status !== 'TODOS') {
            $query->where('status', $status);
        }

        $propostas = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $propostasAutocomplete = $propostas->getCollection()
            ->flatMap(function ($proposta) {
                return array_filter([
                    $proposta->codigo,
                    $proposta->id ? '#'.$proposta->id : null,
                    $proposta->cliente?->razao_social,
                    $proposta->status,
                ]);
            })
            ->unique()
            ->values();

        return view('comercial.propostas.index', [
            'propostas' => $propostas,
            'propostasAutocomplete' => $propostasAutocomplete,
        ]);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id ?? 1;
        $isMaster = $user->hasPapel('Master');

        $esocialId = config('services.esocial_id');

        $clienteSelecionadoId = (int) $request->query('cliente_id');
        if ($clienteSelecionadoId > 0) {
            $clienteSelecionadoId = (int) Cliente::where('id', $clienteSelecionadoId)
                ->where('empresa_id', $empresaId)
                ->value('id');
        } else {
            $clienteSelecionadoId = 0;
        }

        $clientes = Cliente::where('empresa_id', $empresaId)->orderByDesc('id')->get();
        $servicos = Servico::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->when($esocialId, fn($q) => $q->where('id', '!=', $esocialId))
            ->orderBy('nome')
            ->get();
        $funcoes = Funcao::where('empresa_id', $empresaId)->orderBy('nome')->get(['id', 'nome']);

        $treinamentos = collect();
        $treinamentoId = (int) (config('services.treinamento_id') ?? 0);
        $padrao = TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->first();
        if ($padrao && $treinamentoId > 0) {
            $treinamentos = TabelaPrecoItem::query()
                ->where('tabela_preco_padrao_id', $padrao->id)
                ->where('servico_id', $treinamentoId)
                ->where('ativo', true)
                ->whereNotNull('codigo')
                ->orderBy('codigo')
                ->selectRaw('id, codigo, descricao as titulo')
                ->get();
        }

        $formasPagamento = [
            'Pix',
            'Boleto',
            'Cartão de crédito',
            'Cartão de débito',
            'Transferência',
        ];

        $ultimaPropostaPorCliente = Proposta::query()
            ->where('empresa_id', $empresaId)
            ->orderByDesc('id')
            ->get(['id', 'cliente_id', 'vendedor_id'])
            ->groupBy('cliente_id')
            ->map(function ($rows) use ($isMaster, $user) {
                $proposta = $rows->first();
                $canEdit = $isMaster || ((int) $proposta->vendedor_id === (int) $user->id);
                return [
                    'id' => $proposta->id,
                    'can_edit' => $canEdit,
                    'edit_url' => $canEdit ? route('comercial.propostas.edit', $proposta) : null,
                    'show_url' => route('comercial.propostas.show', $proposta),
                ];
            })
            ->all();

        $propostaAsoGrupos = collect();

        return view('comercial.propostas.create', compact(
            'clientes',
            'servicos',
            'formasPagamento',
            'user',
            'treinamentos',
            'funcoes',
            'propostaAsoGrupos',
            'ultimaPropostaPorCliente',
            'clienteSelecionadoId'
        ));
    }

    public function edit(Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $empresaId = $user->empresa_id ?? 1;

        $esocialId = config('services.esocial_id');

        $clientes = Cliente::where('empresa_id', $empresaId)->orderByDesc('id')->get();
        $servicos = Servico::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->when($esocialId, fn($q) => $q->where('id', '!=', $esocialId))
            ->orderBy('nome')
            ->get();
        $funcoes = Funcao::where('empresa_id', $empresaId)->orderBy('nome')->get(['id', 'nome']);

        $treinamentos = collect();
        $treinamentoId = (int) (config('services.treinamento_id') ?? 0);
        $padrao = TabelaPrecoPadrao::where('empresa_id', $empresaId)
            ->where('ativa', true)
            ->first();
        if ($padrao && $treinamentoId > 0) {
            $treinamentos = TabelaPrecoItem::query()
                ->where('tabela_preco_padrao_id', $padrao->id)
                ->where('servico_id', $treinamentoId)
                ->where('ativo', true)
                ->whereNotNull('codigo')
                ->orderBy('codigo')
                ->selectRaw('id, codigo, descricao as titulo')
                ->get();
        }

        $formasPagamento = [
            'Pix',
            'Boleto',
            'Cartão de crédito',
            'Cartão de débito',
            'Transferência',
        ];

        $proposta->load('itens');
        $propostaAsoGrupos = \App\Models\PropostaAsoGrupo::query()
            ->where('proposta_id', $proposta->id)
            ->with(['grupo', 'clienteGhe'])
            ->get();

        return view('comercial.propostas.create', compact('clientes','servicos','formasPagamento','user','treinamentos','proposta','funcoes','propostaAsoGrupos'));
    }

    public function store(Request $request)
    {
        return $this->saveProposta($request);
    }

    public function update(Request $request, Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        return $this->saveProposta($request, $proposta);
    }

    public function destroy(Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        return DB::transaction(function () use ($proposta) {
            $proposta->itens()->delete();
            PropostaAsoGrupo::query()
                ->where('proposta_id', $proposta->id)
                ->delete();
            $proposta->delete();

            return redirect()
                ->route('comercial.propostas.index')
                ->with('ok', 'Proposta removida.');
        });
    }

    public function enviarWhatsapp(Request $request, Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $data = $request->validate([
            'telefone' => ['required', 'string', 'max:30'],
            'mensagem' => ['required', 'string', 'max:2000'],
        ]);

        $digits = preg_replace('/\\D+/', '', $data['telefone']) ?? '';
        if ($digits === '') {
            return back()->with('erro', 'Telefone inválido.');
        }

        $publicLink = $this->ensurePublicLink($proposta);
        $mensagem = $this->appendPublicLink($data['mensagem'], $publicLink);

        $proposta->update([
            'status' => 'ENVIADA',
            'pipeline_status' => 'PROPOSTA_ENVIADA',
            'pipeline_updated_at' => now(),
            'pipeline_updated_by' => $user->id,
            'perdido_motivo' => null,
            'perdido_observacao' => null,
        ]);

        $url = 'https://wa.me/' . $digits . '?text=' . urlencode($mensagem);
        return redirect()->away($url);
    }

    public function enviarEmail(Request $request, Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'assunto' => ['required', 'string', 'max:120'],
            'mensagem' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $publicLink = $this->ensurePublicLink($proposta);
            $mensagem = $this->appendPublicLink($data['mensagem'], $publicLink);

            Mail::raw($mensagem, function ($m) use ($data) {
                $m->to($data['email'])->subject($data['assunto']);
            });

            $proposta->update([
                'status' => 'ENVIADA',
                'pipeline_status' => 'PROPOSTA_ENVIADA',
                'pipeline_updated_at' => now(),
                'pipeline_updated_by' => $user->id,
                'perdido_motivo' => null,
                'perdido_observacao' => null,
            ]);

            return back()->with('ok', 'E-mail enviado.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('erro', 'Falha ao enviar e-mail.');
        }
    }

    public function duplicar(Request $request, Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        if (!$user->hasPapel('Master')) {
            abort_unless((int) $proposta->vendedor_id === (int) $user->id, 403);
        }

        $data = $request->validate([
            'cliente_id' => ['required', 'integer'],
        ]);

        $clienteId = (int) $data['cliente_id'];
        $clienteOk = Cliente::where('id', $clienteId)
            ->where('empresa_id', $user->empresa_id)
            ->exists();
        abort_if(!$clienteOk, 403);

        $temPropostaAberta = Proposta::query()
            ->where('empresa_id', $user->empresa_id)
            ->where('cliente_id', $clienteId)
            ->whereNotIn('status', ['FECHADA', 'CANCELADA'])
            ->exists();

        if ($temPropostaAberta) {
            return back()->with('erro', 'Já existe uma proposta em aberto para este cliente.');
        }

        $proposta->load(['itens', 'asoGrupos']);

        $valorItens = $proposta->itens->sum(fn (PropostaItens $it) => (float) $it->valor_total);
        $temItemEsocial = $proposta->itens->contains(fn (PropostaItens $it) => strtoupper((string) $it->tipo) === 'ESOCIAL');
        $valorEsocial = $proposta->incluir_esocial && !$temItemEsocial
            ? (float) ($proposta->esocial_valor_mensal ?? 0)
            : 0.0;
        $valorTotal = $valorItens + $valorEsocial;

        $codigo = 'PRP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));

        $novaProposta = DB::transaction(function () use ($proposta, $clienteId, $valorTotal, $codigo) {
            $novaProposta = Proposta::create([
                'empresa_id' => $proposta->empresa_id,
                'cliente_id' => $clienteId,
                'vendedor_id' => auth()->id(),
                'codigo' => $codigo,
                'forma_pagamento' => $proposta->forma_pagamento,
                'prazo_dias' => $proposta->prazo_dias,
                'vencimento_servicos' => $proposta->vencimento_servicos,
                'incluir_esocial' => $proposta->incluir_esocial,
                'esocial_qtd_funcionarios' => $proposta->esocial_qtd_funcionarios,
                'esocial_valor_mensal' => $proposta->esocial_valor_mensal,
                'valor_total' => $valorTotal,
                'status' => 'PENDENTE',
                'pipeline_status' => 'CONTATO_INICIAL',
                'pipeline_updated_at' => now(),
                'pipeline_updated_by' => auth()->id(),
                'public_token' => null,
                'public_responded_at' => null,
                'perdido_motivo' => null,
                'perdido_observacao' => null,
                'observacoes' => $proposta->observacoes,
            ]);

            foreach ($proposta->itens as $item) {
                PropostaItens::create([
                    'proposta_id' => $novaProposta->id,
                    'servico_id' => $item->servico_id,
                    'tipo' => $item->tipo,
                    'nome' => $item->nome,
                    'descricao' => $item->descricao,
                    'valor_unitario' => $item->valor_unitario,
                    'acrescimo' => $item->acrescimo,
                    'desconto' => $item->desconto,
                    'quantidade' => $item->quantidade,
                    'prazo' => $item->prazo,
                    'valor_total' => $item->valor_total,
                    'meta' => $item->meta,
                ]);
            }

            foreach ($proposta->asoGrupos as $grupo) {
                PropostaAsoGrupo::create([
                    'empresa_id' => $proposta->empresa_id,
                    'cliente_id' => $clienteId,
                    'proposta_id' => $novaProposta->id,
                    'tipo_aso' => $grupo->tipo_aso,
                    'grupo_exames_id' => $grupo->grupo_exames_id,
                    'total_exames' => $grupo->total_exames,
                ]);
            }

            return $novaProposta;
        });

        return redirect()
            ->route('comercial.propostas.edit', $novaProposta)
            ->with('ok', 'Proposta duplicada. Faça os ajustes necessários.');
    }

    public function alterarStatus(Request $request, Proposta $proposta, PropostaService $service)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);
        if (!$user->hasPapel('Master')) {
            abort_unless((int) $proposta->vendedor_id === (int) $user->id, 403);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:PENDENTE,ENVIADA,FECHADA,CANCELADA'],
        ]);

        $atual = strtoupper($proposta->status ?? '');
        $novo = strtoupper($data['status']);

        $permitido = match ($atual) {
            'PENDENTE' => in_array($novo, ['PENDENTE','ENVIADA','CANCELADA']),
            'ENVIADA'  => in_array($novo, ['ENVIADA','FECHADA','CANCELADA']),
            default    => false,
        };

        if (!$permitido) {
            return response()->json(['message' => 'Transição não permitida.'], 422);
        }

        if ($novo === 'FECHADA') {
            $service->fechar($proposta->id, $user->id);
        } else {
            $pipelineStatus = match ($novo) {
                'ENVIADA' => 'PROPOSTA_ENVIADA',
                'CANCELADA' => 'PERDIDO',
                default => $proposta->pipeline_status ?? 'CONTATO_INICIAL',
            };

            $payload = [
                'status' => $novo,
                'pipeline_status' => $pipelineStatus,
                'pipeline_updated_at' => now(),
                'pipeline_updated_by' => $user->id,
            ];

            if ($novo === 'CANCELADA') {
                $payload['perdido_motivo'] = $proposta->perdido_motivo ?: 'Cancelada';
            } else {
                $payload['perdido_motivo'] = null;
                $payload['perdido_observacao'] = null;
            }

            $proposta->update($payload);
        }

        return response()->json([
            'message' => 'Status atualizado.',
            'status' => $novo,
        ]);
    }

    private function saveProposta(Request $request, ?Proposta $proposta = null)
    {
        $empresaId = auth()->user()->empresa_id;
        $user = auth()->user();
        $isMaster = $user->hasPapel('Master');

        if ($request->boolean('incluir_esocial')) {
            $itensInput = $request->input('itens', []);
            $itensInput = is_array($itensInput) ? $itensInput : [];
            $valorEsocial = (float) ($request->input('esocial_valor_mensal') ?? 0);

            $esocialIndex = null;
            foreach ($itensInput as $idx => $item) {
                if (strtoupper((string) ($item['tipo'] ?? '')) === 'ESOCIAL') {
                    $esocialIndex = $idx;
                    break;
                }
            }

            $servicoEsocialId = (int) (config('services.esocial_id') ?? 0);
            $esocialItem = [
                'servico_id' => $servicoEsocialId > 0 ? $servicoEsocialId : null,
                'tipo' => 'ESOCIAL',
                'nome' => 'eSocial',
                'descricao' => 'eSocial',
                'valor_unitario' => $valorEsocial,
                'quantidade' => 1,
                'prazo' => null,
                'acrescimo' => 0,
                'desconto' => 0,
                'valor_total' => $valorEsocial,
                'meta' => null,
            ];

            if ($esocialIndex === null) {
                $itensInput[] = $esocialItem;
            } else {
                $itensInput[$esocialIndex] = array_merge($itensInput[$esocialIndex], $esocialItem);
            }

            $request->merge(['itens' => $itensInput]);
        }

        $data = $request->validate([
            'cliente_id' => ['required','integer'],
            'forma_pagamento' => ['required','string','max:80'],
            'prazo_dias' => ['required','integer','min:1','max:365'],
            'vencimento_servicos' => ['required','integer','min:1','max:31'],

            'incluir_esocial' => ['nullable','boolean'],
            'esocial_qtd_funcionarios' => ['nullable','integer','min:0'],
            'esocial_valor_mensal' => ['nullable','numeric','min:0'],

            'cliente_aso_grupos' => ['nullable','array'],
            'cliente_aso_grupos.*.ghe_id' => ['nullable','integer','exists:ghes,id'],
            'cliente_aso_grupos.*.cliente_ghe_id' => ['nullable','integer','exists:cliente_ghes,id'],
            'cliente_aso_grupos.*.ghe_nome' => ['nullable','string','max:255'],
            'cliente_aso_grupos.*.tipos' => ['nullable','array'],
            'cliente_aso_grupos.*.tipos.*.grupo_id' => ['nullable','integer','exists:protocolos_exames,id'],
            'cliente_aso_grupos.*.tipos.*.total_exames' => ['nullable','numeric','min:0'],

            'itens' => ['required','array','min:1'],
            'itens.*.servico_id' => ['nullable','integer'],
            'itens.*.tipo' => ['required','string','max:40'],
            'itens.*.nome' => ['required','string','max:255'],
            'itens.*.descricao' => ['nullable','string','max:255'],
            'itens.*.valor_unitario' => ['required','numeric','min:0'],
            'itens.*.quantidade' => ['required','integer','min:1'],
            'itens.*.prazo' => ['nullable','string','max:60'],
            'itens.*.acrescimo' => ['nullable','numeric','min:0'],
            'itens.*.desconto' => ['nullable','numeric','min:0'],
            'itens.*.valor_total' => ['required','numeric','min:0'],
            'itens.*.meta' => ['nullable', function (string $attribute, mixed $value, \Closure $fail) {
                if (is_null($value) || $value === '') {
                    return;
                }

                if (is_array($value)) {
                    return;
                }

                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                        $fail('Meta inválida.');
                    }
                    return;
                }

                $fail('Meta inválida.');
            }],
        ]);

        // Permite múltiplas propostas por cliente (não reaproveita proposta existente)

        $servicoEsocialId = (int) (config('services.esocial_id') ?? 0);
        $servicoExameId = (int) (config('services.exame_id') ?? 0);
        $servicoAsoId = (int) (config('services.aso_id') ?? 0);

        foreach ($data['itens'] as $idx => $it) {
            if (!array_key_exists('meta', $it) || $it['meta'] === null || $it['meta'] === '') {
                $data['itens'][$idx]['meta'] = null;
                continue;
            }

            if (is_string($it['meta'])) {
                $decoded = json_decode($it['meta'], true);
                $data['itens'][$idx]['meta'] = is_array($decoded) ? $decoded : null;
            }

            if (($data['itens'][$idx]['tipo'] ?? '') === 'ESOCIAL' && $servicoEsocialId > 0 && empty($data['itens'][$idx]['servico_id'])) {
                $data['itens'][$idx]['servico_id'] = $servicoEsocialId;
            }

            if (in_array($data['itens'][$idx]['tipo'] ?? '', ['EXAME','PACOTE_EXAMES'], true) && $servicoExameId > 0 && empty($data['itens'][$idx]['servico_id'])) {
                $data['itens'][$idx]['servico_id'] = $servicoExameId;
            }

            if (($data['itens'][$idx]['tipo'] ?? '') === 'ASO_TIPO' && $servicoAsoId > 0 && empty($data['itens'][$idx]['servico_id'])) {
                $data['itens'][$idx]['servico_id'] = $servicoAsoId;
            }
        }

        $asoTipos = ['admissional', 'periodico', 'demissional', 'mudanca_funcao', 'retorno_trabalho'];
        $clienteAsoInput = $data['cliente_aso_grupos'] ?? [];
        $clienteAsoGrupos = [];
        $asoGrupos = [];
        $clienteGheCache = [];
        foreach ($clienteAsoInput as $cfg) {
            $clienteGheId = (int) ($cfg['cliente_ghe_id'] ?? 0);
            $gheId = (int) ($cfg['ghe_id'] ?? 0);
            $gheNome = trim((string) ($cfg['ghe_nome'] ?? ''));

            $clienteGhe = null;
            if ($clienteGheId > 0) {
                $clienteGhe = ClienteGhe::query()
                    ->where('empresa_id', $empresaId)
                    ->where('cliente_id', $data['cliente_id'])
                    ->where('id', $clienteGheId)
                    ->first();
                abort_if(!$clienteGhe, 403);
            } elseif ($gheId > 0) {
                if (isset($clienteGheCache['ghe:' . $gheId])) {
                    $clienteGhe = $clienteGheCache['ghe:' . $gheId];
                } else {
                    $ghe = Ghe::query()
                        ->where('empresa_id', $empresaId)
                        ->where('id', $gheId)
                        ->first();
                    abort_if(!$ghe, 403);
                    $clienteGhe = ClienteGhe::query()
                        ->where('empresa_id', $empresaId)
                        ->where('cliente_id', $data['cliente_id'])
                        ->where('ghe_id', $gheId)
                        ->first();
                    if (!$clienteGhe) {
                        $clienteGhe = ClienteGhe::create([
                            'empresa_id' => $empresaId,
                            'cliente_id' => $data['cliente_id'],
                            'ghe_id' => $gheId,
                            'nome' => $gheNome !== '' ? $gheNome : $ghe->nome,
                            'protocolo_id' => $ghe->grupo_exames_id,
                            'base_aso_admissional' => (float) ($ghe->base_aso_admissional ?? 0),
                            'base_aso_periodico' => (float) ($ghe->base_aso_periodico ?? 0),
                            'base_aso_demissional' => (float) ($ghe->base_aso_demissional ?? 0),
                            'base_aso_mudanca_funcao' => (float) ($ghe->base_aso_mudanca_funcao ?? 0),
                            'base_aso_retorno_trabalho' => (float) ($ghe->base_aso_retorno_trabalho ?? 0),
                            'preco_fechado_admissional' => $ghe->preco_fechado_admissional ?? null,
                            'preco_fechado_periodico' => $ghe->preco_fechado_periodico ?? null,
                            'preco_fechado_demissional' => $ghe->preco_fechado_demissional ?? null,
                            'preco_fechado_mudanca_funcao' => $ghe->preco_fechado_mudanca_funcao ?? null,
                            'preco_fechado_retorno_trabalho' => $ghe->preco_fechado_retorno_trabalho ?? null,
                            'ativo' => true,
                        ]);
                        $gheFuncoes = $ghe->funcoes()->pluck('funcao_id')->all();
                        foreach ($gheFuncoes as $funcaoId) {
                            \App\Models\ClienteGheFuncao::create([
                                'cliente_ghe_id' => $clienteGhe->id,
                                'funcao_id' => $funcaoId,
                            ]);
                        }
                    }
                    $clienteGheCache['ghe:' . $gheId] = $clienteGhe;
                }
            }

            if (!$clienteGhe) {
                continue;
            }

            $tipos = is_array($cfg['tipos'] ?? null) ? $cfg['tipos'] : [];
            foreach ($asoTipos as $tipo) {
                $row = $tipos[$tipo] ?? [];
                $grupoId = (int) ($row['grupo_id'] ?? 0);
                if ($grupoId <= 0) {
                    continue;
                }
                $totalExames = (float) ($row['total_exames'] ?? 0);
                $clienteAsoGrupos[] = [
                    'cliente_ghe_id' => $clienteGhe->id,
                    'tipo_aso' => $tipo,
                    'grupo_id' => $grupoId,
                    'total_exames' => $totalExames,
                ];
                $asoGrupos[] = [
                    'cliente_ghe_id' => $clienteGhe->id,
                    'tipo_aso' => $tipo,
                    'grupo_id' => $grupoId,
                    'total_exames' => $totalExames,
                ];
            }
        }

        $clienteOk = Cliente::where('id', $data['cliente_id'])
            ->where('empresa_id', $empresaId)
            ->exists();
        abort_if(!$clienteOk, 403);

        // seta vendedor no cliente se estiver vazio
        $cliente = Cliente::find($data['cliente_id']);
        if ($cliente && !$cliente->vendedor_id) {
            $cliente->update(['vendedor_id' => auth()->id()]);
        }

        foreach ($data['itens'] as $it) {
            if (!empty($it['servico_id'])) {
                $ok = Servico::where('id', $it['servico_id'])
                    ->where('empresa_id', $empresaId)
                    ->exists();
                abort_if(!$ok, 403);
            }
        }

        foreach ($asoGrupos as $row) {
            $ok = ProtocoloExame::where('empresa_id', $empresaId)
                ->where('id', $row['grupo_id'])
                ->exists();
            abort_if(!$ok, 403);
        }

        $incluirEsocial = !empty($data['incluir_esocial']);

        $isAsoItem = function (array $it): bool {
            if (strtoupper((string) ($it['tipo'] ?? '')) === 'ASO_TIPO') {
                return true;
            }

            if (!empty($it['meta']['aso_tipo'])) {
                return true;
            }

            $nomeBase = strtoupper((string) ($it['nome'] ?? $it['descricao'] ?? ''));
            return $nomeBase !== '' && str_contains($nomeBase, 'ASO');
        };
        $hasAsoTipoItems = false;
        $asoIndexes = [];
        foreach ($data['itens'] as $idx => $it) {
            if ($isAsoItem($it)) {
                $asoIndexes[] = $idx;
            }
            if (!empty($it['meta']['aso_tipo'])) {
                $hasAsoTipoItems = true;
            }
        }
        $hasAsoGrupos = !empty($asoGrupos);

        $gheTotal = 0.0;
        if (!$hasAsoGrupos && !$hasAsoTipoItems && !empty($data['cliente_id'])) {
            $gheSnapshot = app(AsoGheService::class)
                ->buildSnapshotForCliente((int) $data['cliente_id'], $empresaId);
            foreach (($gheSnapshot['ghes'] ?? []) as $ghe) {
                $gheTotal += (float) ($ghe['total_exames_por_tipo']['admissional'] ?? ($ghe['total_exames'] ?? 0));
            }
        }

        if ($gheTotal > 0 && empty($asoIndexes)) {
            $asoServicoId = (int) (config('services.aso_id') ?? 0);
            $data['itens'][] = [
                'servico_id' => $asoServicoId ?: null,
                'tipo' => 'SERVICO',
                'nome' => 'ASO',
                'descricao' => 'ASO por GHE',
                'valor_unitario' => $gheTotal,
                'quantidade' => 1,
                'prazo' => null,
                'acrescimo' => 0,
                'desconto' => 0,
                'valor_total' => $gheTotal,
                'meta' => null,
            ];
            $asoIndexes[] = count($data['itens']) - 1;
        }

        if ($gheTotal > 0 && !empty($asoIndexes)) {
            $totalAsoAtual = 0.0;
            foreach ($asoIndexes as $idx) {
                $totalAsoAtual += (float) ($data['itens'][$idx]['valor_total'] ?? 0);
            }
            if ($totalAsoAtual <= 0) {
                $idx = $asoIndexes[0];
                $qtd = max(1, (int) ($data['itens'][$idx]['quantidade'] ?? 1));
                $data['itens'][$idx]['valor_unitario'] = $gheTotal / $qtd;
                $data['itens'][$idx]['valor_total'] = $gheTotal;
            }
        }

        $valorItens = 0.0;
        $temItemEsocial = false;
        foreach ($data['itens'] as $it) {
            $valorItens += (float) $it['valor_total'];
            if (($it['tipo'] ?? '') === 'ESOCIAL') {
                $temItemEsocial = true;
            }
        }

        $valorEsocialCampo = $incluirEsocial ? (float) ($data['esocial_valor_mensal'] ?? 0) : 0.0;
        $valorEsocial = $temItemEsocial ? 0.0 : $valorEsocialCampo;
        $valorTotal = $valorItens + $valorEsocial;

        $codigo = $proposta?->codigo ?? ('PRP-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4)));
        $prazoDias = (int) $data['prazo_dias'];
        $vencimentoServicos = $data['vencimento_servicos'];

        return DB::transaction(function () use ($empresaId, $data, $codigo, $valorTotal, $incluirEsocial, $valorEsocial, $valorEsocialCampo, $proposta, $prazoDias, $vencimentoServicos, $asoGrupos, $clienteAsoGrupos) {
            $payload = [
                'empresa_id' => $empresaId,
                'cliente_id' => $data['cliente_id'],
                'vendedor_id' => $proposta?->vendedor_id ?? auth()->id(),
                'codigo' => $codigo,
                'forma_pagamento' => $data['forma_pagamento'],
                'prazo_dias' => $prazoDias,
                'vencimento_servicos' => $vencimentoServicos,

                'incluir_esocial' => $incluirEsocial,
                'esocial_qtd_funcionarios' => $incluirEsocial ? ($data['esocial_qtd_funcionarios'] ?? 0) : null,
                'esocial_valor_mensal' => $incluirEsocial ? $valorEsocialCampo : 0,

                'valor_total' => $valorTotal,
            ];

            $contratoParaAtualizar = null;
            if ($proposta) {
                $payload['status'] = 'PENDENTE';
                $payload['public_responded_at'] = null;
                $payload['pipeline_status'] = 'CONTATO_INICIAL';
                $payload['pipeline_updated_at'] = now();
                $payload['pipeline_updated_by'] = auth()->id();
                $payload['perdido_motivo'] = null;
                $payload['perdido_observacao'] = null;

                $contratoParaAtualizar = \App\Models\ClienteContrato::query()
                    ->where('empresa_id', $empresaId)
                    ->where('proposta_id_origem', $proposta->id)
                    ->latest('id')
                    ->first();

                $proposta->update($payload);
                $proposta->itens()->delete();
            } else {
                $payload['status'] = 'PENDENTE';
                $payload['pipeline_status'] = 'CONTATO_INICIAL';
                $proposta = Proposta::create($payload);
            }

            PropostaAsoGrupo::query()
                ->where('proposta_id', $proposta->id)
                ->delete();

            ClienteAsoGrupo::query()
                ->where('empresa_id', $empresaId)
                ->where('cliente_id', $data['cliente_id'])
                ->delete();

            foreach ($clienteAsoGrupos as $row) {
                ClienteAsoGrupo::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $data['cliente_id'],
                    'cliente_ghe_id' => $row['cliente_ghe_id'],
                    'tipo_aso' => $row['tipo_aso'],
                    'grupo_exames_id' => $row['grupo_id'],
                    'total_exames' => $row['total_exames'],
                ]);
            }

            foreach ($asoGrupos as $row) {
                PropostaAsoGrupo::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $data['cliente_id'],
                    'cliente_ghe_id' => $row['cliente_ghe_id'] ?? null,
                    'proposta_id' => $proposta->id,
                    'tipo_aso' => $row['tipo_aso'],
                    'grupo_exames_id' => $row['grupo_id'],
                    'total_exames' => $row['total_exames'],
                ]);
            }

            foreach ($data['itens'] as $it) {
                PropostaItens::create([
                    'proposta_id' => $proposta->id,
                    'servico_id' => $it['servico_id'] ?? null,
                    'tipo' => $it['tipo'],
                    'nome' => $it['nome'],
                    'descricao' => $it['descricao'] ?? null,
                    'valor_unitario' => $it['valor_unitario'],
                    'acrescimo' => $it['acrescimo'] ?? 0,
                    'desconto' => $it['desconto'] ?? 0,
                    'quantidade' => $it['quantidade'],
                    'prazo' => $it['prazo'] ?? null,
                    'valor_total' => $it['valor_total'],
                    'meta' => $it['meta'] ?? null,
                ]);
            }

            if ($contratoParaAtualizar && strtoupper((string) $proposta->status) === 'FECHADA') {
                $contratoParaAtualizar->load(['itens.servico', 'cliente']);
                $contratoParaAtualizar->update([
                    'vencimento_servicos' => $vencimentoServicos,
                ]);

                $usuarioNome = auth()->user()?->name ?? 'Sistema';
                $clienteNome = $contratoParaAtualizar->cliente?->razao_social ?? 'Cliente';

                $oldMap = [];
                foreach ($contratoParaAtualizar->itens as $item) {
                    $key = $item->servico_id
                        ? 'id:' . $item->servico_id
                        : 'nome:' . strtolower((string) ($item->descricao_snapshot ?? $item->servico?->nome ?? ''));
                    $oldMap[$key] = [
                        'preco' => (float) $item->preco_unitario_snapshot,
                        'servico_id' => $item->servico_id,
                        'servico_nome' => $item->servico?->nome ?? $item->descricao_snapshot ?? 'Serviço',
                    ];
                }

                $newMap = [];
                foreach ($data['itens'] as $it) {
                    $key = !empty($it['servico_id'])
                        ? 'id:' . $it['servico_id']
                        : 'nome:' . strtolower((string) ($it['nome'] ?? ''));
                    $newMap[$key] = [
                        'preco' => (float) ($it['valor_total'] ?? $it['valor_unitario'] ?? 0),
                        'servico_id' => $it['servico_id'] ?? null,
                        'servico_nome' => $it['nome'] ?? $it['descricao'] ?? 'Serviço',
                    ];
                }

                $asoServicoId = app(AsoGheService::class)
                    ->resolveServicoAsoIdFromContrato($contratoParaAtualizar);
                $isAsoItem = function (array $it) use ($asoServicoId): bool {
                    if ($asoServicoId && (int) ($it['servico_id'] ?? 0) === (int) $asoServicoId) {
                        return true;
                    }

                    if (strtoupper((string) ($it['tipo'] ?? '')) === 'ASO_TIPO') {
                        return true;
                    }

                    if (!empty($it['meta']['aso_tipo'])) {
                        return true;
                    }

                    $nomeBase = strtoupper((string) ($it['nome'] ?? $it['descricao'] ?? ''));
                    return $nomeBase !== '' && str_contains($nomeBase, 'ASO');
                };

                $temAso = collect($data['itens'])->contains(fn ($it) => $isAsoItem($it));
                $asoSnapshot = null;
                if ($temAso) {
                    $asoSnapshot = app(AsoGheService::class)
                        ->buildSnapshotForCliente((int) $data['cliente_id'], $empresaId);
                    if (empty($asoSnapshot['ghes'])) {
                        $asoSnapshot = null;
                    } else {
                        $asoSnapshot = app(AsoGheService::class)
                            ->applyAsoGrupoOverrides($asoSnapshot, $asoGrupos);
                    }
                }

                $contratoParaAtualizar->itens()->delete();
                foreach ($data['itens'] as $it) {
                    $regrasSnapshot = null;
                    if ($isAsoItem($it)) {
                        $regrasSnapshot = $this->buildRegrasSnapshotAso($it, $asoSnapshot);
                    }

                    $descricaoSnapshot = $it['descricao'] ?? $it['nome'];
                    if (!empty($it['meta']['aso_tipo'])) {
                        $descricaoSnapshot = $it['nome'] ?? $it['descricao'];
                    }

                    ClienteContratoItem::create([
                        'cliente_contrato_id' => $contratoParaAtualizar->id,
                        'servico_id' => $it['servico_id'] ?? null,
                        'descricao_snapshot' => $descricaoSnapshot,
                        'preco_unitario_snapshot' => $it['valor_total'] ?? $it['valor_unitario'],
                        'unidade_cobranca' => 'unidade',
                        'regras_snapshot' => $regrasSnapshot,
                        'ativo' => true,
                    ]);
                }

                ClienteContratoLog::create([
                    'cliente_contrato_id' => $contratoParaAtualizar->id,
                    'user_id' => auth()->id(),
                    'acao' => 'ATUALIZACAO_ITENS',
                    'descricao' => sprintf(
                        'USUARIO: %s ATUALIZOU os itens do contrato da empresa %s via proposta #%s.',
                        $usuarioNome,
                        $clienteNome,
                        $proposta->id
                    ),
                ]);

                foreach ($oldMap as $key => $oldItem) {
                    if (!array_key_exists($key, $newMap)) {
                        ClienteContratoLog::create([
                            'cliente_contrato_id' => $contratoParaAtualizar->id,
                            'user_id' => auth()->id(),
                            'servico_id' => $oldItem['servico_id'],
                            'acao' => 'SERVICO_REMOVIDO',
                            'descricao' => sprintf(
                                'USUARIO: %s REMOVEU o serviço %s do contrato da empresa %s.',
                                $usuarioNome,
                                $oldItem['servico_nome'],
                                $clienteNome
                            ),
                            'valor_anterior' => $oldItem['preco'],
                        ]);
                    }
                }

                foreach ($newMap as $key => $newItem) {
                    if (!array_key_exists($key, $oldMap)) {
                        ClienteContratoLog::create([
                            'cliente_contrato_id' => $contratoParaAtualizar->id,
                            'user_id' => auth()->id(),
                            'servico_id' => $newItem['servico_id'],
                            'acao' => 'SERVICO_CRIADO',
                            'descricao' => sprintf(
                                'USUARIO: %s ADICIONOU o serviço %s ao contrato da empresa %s. Valor: R$ %s.',
                                $usuarioNome,
                                $newItem['servico_nome'],
                                $clienteNome,
                                number_format($newItem['preco'], 2, ',', '.')
                            ),
                            'valor_novo' => $newItem['preco'],
                        ]);
                        continue;
                    }

                    $oldPreco = (float) ($oldMap[$key]['preco'] ?? 0);
                    $novoPreco = (float) ($newItem['preco'] ?? 0);
                    if (abs($oldPreco - $novoPreco) >= 0.01) {
                        ClienteContratoLog::create([
                            'cliente_contrato_id' => $contratoParaAtualizar->id,
                            'user_id' => auth()->id(),
                            'servico_id' => $newItem['servico_id'] ?? $oldMap[$key]['servico_id'],
                            'acao' => 'ALTERACAO',
                            'descricao' => sprintf(
                                'USUARIO: %s ALTEROU o contrato da empresa %s. SERVICO %s. Valor antigo: R$ %s. Novo valor: R$ %s.',
                                $usuarioNome,
                                $clienteNome,
                                $newItem['servico_nome'],
                                number_format($oldPreco, 2, ',', '.'),
                                number_format($novoPreco, 2, ',', '.')
                            ),
                            'valor_anterior' => $oldPreco,
                            'valor_novo' => $novoPreco,
                        ]);
                    }
                }
            }

            return redirect()
                ->route('comercial.propostas.show', $proposta)
                ->with('ok', $proposta->wasRecentlyCreated ? 'Proposta criada com sucesso.' : 'Proposta atualizada com sucesso.');
        });
    }

    public function show(Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);
        $canEdit = $user->hasPapel('Master') || ((int) $proposta->vendedor_id === (int) $user->id);

        $proposta->load(['cliente', 'empresa', 'vendedor', 'itens', 'asoGrupos.grupo.itens.exame']);
        $unidades = UnidadeClinica::where('empresa_id', $user->empresa_id)
            ->where('ativo', true)
            ->get();
        $gheSnapshot = [];
        if ($proposta->cliente_id) {
            $gheSnapshot = app(AsoGheService::class)
                ->buildSnapshotForCliente($proposta->cliente_id, $user->empresa_id);
            if (!empty($gheSnapshot['ghes']) && $proposta->asoGrupos->isNotEmpty()) {
                $gheSnapshot = app(AsoGheService::class)
                    ->applyAsoGrupoOverrides($gheSnapshot, $proposta->asoGrupos);
            }
        }


        $publicLink = $this->ensurePublicLink($proposta);
        $empresa = Empresa::find($proposta->empresa_id);

        return view('comercial.propostas.show', [
            'proposta' => $proposta,
            'empresa' => $empresa,
            'publicLink' => $publicLink,
            'unidades' => $unidades,
            'gheSnapshot' => $gheSnapshot,
            'canEdit' => $canEdit,
        ]);
    }

    public function pdf(Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);
        if (!$user->hasPapel('Master')) {
            abort_unless((int) $proposta->vendedor_id === (int) $user->id, 403);
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $unidades = UnidadeClinica::where('empresa_id', $user->empresa_id)
            ->where('ativo', true)
            ->get();
        $proposta->load(['cliente', 'empresa', 'vendedor', 'itens', 'asoGrupos.grupo.itens.exame']);
        $proposta['unidades'] = $unidades;
        $gheSnapshot = [];
        if ($proposta->cliente_id) {
            $gheSnapshot = app(AsoGheService::class)
                ->buildSnapshotForCliente($proposta->cliente_id, $user->empresa_id);
            if (!empty($gheSnapshot['ghes']) && $proposta->asoGrupos->isNotEmpty()) {
                $gheSnapshot = app(AsoGheService::class)
                    ->applyAsoGrupoOverrides($gheSnapshot, $proposta->asoGrupos);
            }
        }

        $logoPath = public_path('storage/logo.png');
        $logoData = is_file($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $empresa = Empresa::find($proposta->empresa_id);

        $pdf = Pdf::loadView('comercial.propostas.pdf', [
            'proposta' => $proposta,
            'empresa' => $empresa,
            'logoData' => $logoData,
            'gheSnapshot' => $gheSnapshot,
        ])->setPaper('a4');

        $filename = 'proposta-' . ($proposta->codigo ?? $proposta->id) . '.pdf';

        return $pdf->download($filename);
    }

    public function print(Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);
        if (!$user->hasPapel('Master')) {
            abort_unless((int) $proposta->vendedor_id === (int) $user->id, 403);
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        @ini_set('max_execution_time', '180');
        @ini_set('memory_limit', '512M');

        $unidades = UnidadeClinica::where('empresa_id', $user->empresa_id)
            ->where('ativo', true)
            ->get();
        $proposta->load(['cliente', 'empresa', 'vendedor', 'itens', 'asoGrupos.grupo.itens.exame']);
        $proposta['unidades'] = $unidades;
        $gheSnapshot = [];
        if ($proposta->cliente_id) {
            $gheSnapshot = app(AsoGheService::class)
                ->buildSnapshotForCliente($proposta->cliente_id, $user->empresa_id);
            if (!empty($gheSnapshot['ghes']) && $proposta->asoGrupos->isNotEmpty()) {
                $gheSnapshot = app(AsoGheService::class)
                    ->applyAsoGrupoOverrides($gheSnapshot, $proposta->asoGrupos);
            }
        }

        $logoPath = public_path('storage/logo.png');
        $logoData = is_file($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;

        $empresa = Empresa::find($proposta->empresa_id);

        $pdf = Pdf::loadView('comercial.propostas.pdf', [
            'proposta' => $proposta,
            'empresa' => $empresa,
            'logoData' => $logoData,
            'gheSnapshot' => $gheSnapshot,
        ])->setPaper('a4');

        $filename = 'proposta-' . ($proposta->codigo ?? $proposta->id) . '.pdf';

        return $pdf->stream($filename);
    }


    public function fechar(Proposta $proposta, PropostaService $service)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);
        if (!$user->hasPapel('Master')) {
            abort_unless((int) $proposta->vendedor_id === (int) $user->id, 403);
        }

        if (strtoupper((string) $proposta->status) === 'FECHADA') {
            return back()->with('ok','Proposta já está fechada.');
        }

        try {
            $service->fechar($proposta->id, $user->id);

            return redirect()
                ->route('comercial.propostas.show', $proposta)
                ->with('ok','Proposta fechada e contrato do cliente gerado.');
        } catch (\Throwable $e) {
            report($e);
            $message = $e->getMessage() ?: 'Erro ao fechar proposta.';
            if (method_exists($e, 'errors')) {
                $message = collect($e->errors())->flatten()->first() ?? $message;
            }
            return back()->with('erro', $message);
        }
    }

    private function ensurePublicLink(Proposta $proposta): string
    {
        if (!$proposta->public_token) {
            $proposta->public_token = Str::random(40);
            $proposta->save();
        }

        return route('propostas.public.show', $proposta->public_token);
    }

    private function appendPublicLink(string $mensagem, string $link): string
    {
        if (str_contains($mensagem, $link)) {
            return $mensagem;
        }

        return trim($mensagem) . "\n\nAcesse e responda a proposta: {$link}";
    }

    private function buildRegrasSnapshotAso(array $item, ?array $asoSnapshot): ?array
    {
        $meta = $item['meta'] ?? [];
        $asoTipo = $meta['aso_tipo'] ?? null;
        if ($asoTipo) {
            $snapshot = ['aso_tipo' => $asoTipo];
            if (!empty($meta['grupo_id'])) {
                $snapshot['grupo_id'] = (int) $meta['grupo_id'];
            }
            if (!empty($asoSnapshot['ghes'])) {
                $snapshot['ghes'] = $asoSnapshot['ghes'];
            }
            if (!empty($asoSnapshot['funcao_ghe_map'])) {
                $snapshot['funcao_ghe_map'] = $asoSnapshot['funcao_ghe_map'];
            }
            return $snapshot;
        }

        return $asoSnapshot;
    }
}
