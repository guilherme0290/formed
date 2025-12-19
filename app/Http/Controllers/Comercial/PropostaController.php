<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Proposta;
use App\Models\PropostaItens;
use App\Models\Servico;
use App\Models\TreinamentoNrsTabPreco;
use App\Models\UnidadeClinica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\PropostaService;

class PropostaController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        $q = trim((string) $request->query('q', ''));
        $status = strtoupper(trim((string) $request->query('status', '')));

        $query = Proposta::query()
            ->with(['cliente', 'empresa'])
            ->where('empresa_id', $empresaId);

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

        return view('comercial.propostas.index', compact('propostas'));
    }

    public function create()
    {
        $empresaId = auth()->user()->empresa_id;

        $esocialId = config('services.esocial_id');

        $clientes = Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();
        $servicos = Servico::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->when($esocialId, fn($q) => $q->where('id', '!=', $esocialId))
            ->orderBy('nome')
            ->get();

        $treinamentos = TreinamentoNrsTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->orderBy('codigo')
            ->get(['id','codigo','titulo']);

        $formasPagamento = [
            'Pix',
            'Boleto',
            'Cartão de crédito',
            'Cartão de débito',
            'Transferência',
        ];

        $user = auth()->user();

        return view('comercial.propostas.create', compact('clientes','servicos','formasPagamento','user','treinamentos'));
    }

    public function edit(Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $empresaId = $user->empresa_id;

        $esocialId = config('services.esocial_id');

        $clientes = Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();
        $servicos = Servico::where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->when($esocialId, fn($q) => $q->where('id', '!=', $esocialId))
            ->orderBy('nome')
            ->get();

        $treinamentos = TreinamentoNrsTabPreco::query()
            ->where('empresa_id', $empresaId)
            ->where('ativo', true)
            ->orderBy('ordem')
            ->orderBy('codigo')
            ->get(['id','codigo','titulo']);

        $formasPagamento = [
            'Pix',
            'Boleto',
            'Cartão de crédito',
            'Cartão de débito',
            'Transferência',
        ];

        $proposta->load('itens');

        return view('comercial.propostas.create', compact('clientes','servicos','formasPagamento','user','treinamentos','proposta'));
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

        $proposta->update(['status' => 'ENVIADA']);

        $url = 'https://wa.me/' . $digits . '?text=' . urlencode($data['mensagem']);
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
            Mail::raw($data['mensagem'], function ($m) use ($data) {
                $m->to($data['email'])->subject($data['assunto']);
            });

            $proposta->update(['status' => 'ENVIADA']);

            return back()->with('ok', 'E-mail enviado.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('erro', 'Falha ao enviar e-mail.');
        }
    }

    public function alterarStatus(Request $request, Proposta $proposta, PropostaService $service)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:RASCUNHO,ENVIADA,FECHADA,CANCELADA'],
        ]);

        $atual = strtoupper($proposta->status ?? '');
        $novo = strtoupper($data['status']);

        $permitido = match ($atual) {
            'RASCUNHO' => in_array($novo, ['RASCUNHO','ENVIADA']),
            'ENVIADA'  => in_array($novo, ['ENVIADA','FECHADA','CANCELADA']),
            default    => false,
        };

        if (!$permitido) {
            return response()->json(['message' => 'Transição não permitida.'], 422);
        }

        if ($novo === 'FECHADA') {
            $service->fechar($proposta->id, $user->id);
        } else {
            $proposta->update(['status' => $novo]);
        }

        return response()->json([
            'message' => 'Status atualizado.',
            'status' => $novo,
        ]);
    }

    private function saveProposta(Request $request, ?Proposta $proposta = null)
    {
        $empresaId = auth()->user()->empresa_id;

        $data = $request->validate([
            'cliente_id' => ['required','integer'],
            'forma_pagamento' => ['required','string','max:80'],

            'incluir_esocial' => ['nullable','boolean'],
            'esocial_qtd_funcionarios' => ['nullable','integer','min:0'],
            'esocial_valor_mensal' => ['nullable','numeric','min:0'],

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

        $servicoEsocialId = (int) (config('services.esocial_id') ?? 0);
        $servicoExameId = (int) (config('services.exame_id') ?? 0);

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

        $incluirEsocial = !empty($data['incluir_esocial']);

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

        return DB::transaction(function () use ($empresaId, $data, $codigo, $valorTotal, $incluirEsocial, $valorEsocial, $proposta) {
            $payload = [
                'empresa_id' => $empresaId,
                'cliente_id' => $data['cliente_id'],
                'vendedor_id' => $proposta?->vendedor_id ?? auth()->id(),
                'codigo' => $codigo,
                'forma_pagamento' => $data['forma_pagamento'],

                'incluir_esocial' => $incluirEsocial,
                'esocial_qtd_funcionarios' => $incluirEsocial ? ($data['esocial_qtd_funcionarios'] ?? 0) : null,
                'esocial_valor_mensal' => $incluirEsocial ? $valorEsocialCampo : 0,

                'valor_total' => $valorTotal,
            ];

            if ($proposta) {
                $proposta->update($payload);
                $proposta->itens()->delete();
            } else {
                $payload['status'] = 'RASCUNHO';
                $payload['pipeline_status'] = 'CONTATO_INICIAL';
                $proposta = Proposta::create($payload);
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

            return redirect()
                ->route('comercial.propostas.show', $proposta)
                ->with('ok', $proposta->wasRecentlyCreated ? 'Proposta criada com sucesso.' : 'Proposta atualizada com sucesso.');
        });
    }

    public function show(Proposta $proposta)
    {

        $unidades = UnidadeClinica::where('ativo',true)->get();
        $proposta->load(['cliente', 'empresa', 'vendedor', 'itens']);
        $proposta['unidades'] =$unidades;


        return view('comercial.propostas.show', [
            'proposta' => $proposta,
        ]);
    }


    public function fechar(Proposta $proposta, PropostaService $service)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

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
}
