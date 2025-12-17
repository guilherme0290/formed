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
use App\Models\ClienteTabelaPreco;
use App\Models\ClienteTabelaPrecoItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

        foreach ($data['itens'] as $idx => $it) {
            if (!array_key_exists('meta', $it) || $it['meta'] === null || $it['meta'] === '') {
                $data['itens'][$idx]['meta'] = null;
                continue;
            }

            if (is_string($it['meta'])) {
                $decoded = json_decode($it['meta'], true);
                $data['itens'][$idx]['meta'] = is_array($decoded) ? $decoded : null;
            }
        }

        $clienteOk = Cliente::where('id', $data['cliente_id'])
            ->where('empresa_id', $empresaId)
            ->exists();
        abort_if(!$clienteOk, 403);

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
        foreach ($data['itens'] as $it) {
            $valorItens += (float) $it['valor_total'];
        }

        $valorEsocial = $incluirEsocial ? (float) ($data['esocial_valor_mensal'] ?? 0) : 0.0;
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
                'esocial_valor_mensal' => $incluirEsocial ? $valorEsocial : 0,

                'valor_total' => $valorTotal,
            ];

            if ($proposta) {
                $proposta->update($payload);
                $proposta->itens()->delete();
            } else {
                $payload['status'] = 'RASCUNHO';
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


    public function fechar(Proposta $proposta)
    {
        $user = auth()->user();
        abort_unless($proposta->empresa_id === $user->empresa_id, 403);

        if ($proposta->status === 'fechada') {
            return back()->with('ok','Proposta já está fechada.');
        }

        return DB::transaction(function () use ($proposta, $user) {

            // 1) muda status
            $proposta->update(['status' => 'fechada']);

            // 2) encerra tabela vigente do cliente (se existir)
            ClienteTabelaPreco::where('empresa_id', $proposta->empresa_id)
                ->where('cliente_id', $proposta->cliente_id)
                ->where('ativa', true)
                ->update([
                    'ativa' => false,
                    'vigencia_fim' => now(),
                ]);

            // 3) cria nova tabela vigente
            $tabela = ClienteTabelaPreco::create([
                'empresa_id' => $proposta->empresa_id,
                'cliente_id' => $proposta->cliente_id,
                'origem_proposta_id' => $proposta->id,
                'vigencia_inicio' => now(),
                'vigencia_fim' => null,
                'ativa' => true,
                'observacoes' => 'Gerada automaticamente ao fechar proposta '.$proposta->codigo,
            ]);

            // 4) copia itens da proposta (snapshot -> tabela cliente)
            $proposta->load('itens');

            foreach ($proposta->itens as $pi) {
                ClienteTabelaPrecoItem::create([
                    'cliente_tabela_preco_id' => $tabela->id,
                    'servico_id' => $pi->servico_id,
                    'tipo' => $pi->tipo,
                    'codigo' => $pi->meta['codigo'] ?? null, // opcional
                    'nome' => $pi->nome,
                    'descricao' => $pi->descricao,
                    'valor_unitario' => $pi->valor_unitario,
                    'meta' => $pi->meta,
                    'ativo' => true,
                ]);
            }

            return redirect()->route('comercial.propostas.show', $proposta)
                ->with('ok','Proposta fechada e tabela de preço do cliente criada com vigência a partir de hoje.');
        });
    }
}
