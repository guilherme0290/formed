<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteAsoGrupo;
use App\Models\ClienteContrato;
use App\Models\ClienteContratoItem;
use App\Models\ClienteContratoLog;
use App\Models\ClienteGhe;
use App\Models\ClienteUnidadePermitida;
use App\Models\Ghe;
use App\Models\ParametroCliente;
use App\Models\ParametroClienteAsoGrupo;
use App\Models\ParametroClienteItem;
use App\Models\ProtocoloExame;
use App\Models\Servico;
use App\Services\AsoGheService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClienteParametroController extends Controller
{
    public function show(Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        return redirect()->route($this->routeName('edit'), [
            'cliente' => $cliente->id,
            'tab' => 'parametros',
        ]);
    }

    public function save(Request $request, Cliente $cliente)
    {
        $this->authorizeCliente($cliente);

        $empresaId = auth()->user()->empresa_id;

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

        $request->merge(['cliente_id' => $cliente->id]);

        $data = $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id', Rule::in([$cliente->id])],
            'forma_pagamento' => ['required', 'string', 'max:80'],
            'email_envio_fatura' => ['nullable', 'email', 'max:255'],
            'vencimento_servicos' => ['required', 'integer', 'min:1', 'max:31'],

            'incluir_esocial' => ['nullable', 'boolean'],
            'esocial_qtd_funcionarios' => ['nullable', 'integer', 'min:0'],
            'esocial_valor_mensal' => ['nullable', 'numeric', 'min:0'],

            'cliente_aso_grupos' => ['nullable', 'array'],
            'cliente_aso_grupos.*.ghe_id' => ['nullable', 'integer', 'exists:ghes,id'],
            'cliente_aso_grupos.*.cliente_ghe_id' => ['nullable', 'integer', 'exists:cliente_ghes,id'],
            'cliente_aso_grupos.*.ghe_nome' => ['nullable', 'string', 'max:255'],
            'cliente_aso_grupos.*.tipos' => ['nullable', 'array'],
            'cliente_aso_grupos.*.tipos.*.grupo_id' => ['nullable', 'integer', 'exists:protocolos_exames,id'],
            'cliente_aso_grupos.*.tipos.*.total_exames' => ['nullable', 'numeric', 'min:0'],
            'unidades_permitidas' => ['nullable', 'array'],
            'unidades_permitidas.*' => [
                'integer',
                Rule::exists('unidades_clinicas', 'id')->where(function ($q) use ($empresaId) {
                    $q->where('empresa_id', $empresaId);
                }),
            ],

            'itens' => ['required', 'array', 'min:1'],
            'itens.*.servico_id' => ['nullable', 'integer'],
            'itens.*.tipo' => ['required', 'string', 'max:40'],
            'itens.*.nome' => ['required', 'string', 'max:255'],
            'itens.*.descricao' => ['nullable', 'string', 'max:255'],
            'itens.*.valor_unitario' => ['required', 'numeric', 'min:0'],
            'itens.*.quantidade' => ['required', 'integer', 'min:1'],
            'itens.*.prazo' => ['nullable', 'string', 'max:60'],
            'itens.*.acrescimo' => ['nullable', 'numeric', 'min:0'],
            'itens.*.desconto' => ['nullable', 'numeric', 'min:0'],
            'itens.*.valor_total' => ['required', 'numeric', 'min:0'],
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
        ], [
            'required' => 'O campo :attribute é obrigatório.',
            'integer' => 'O campo :attribute deve ser um número inteiro.',
            'numeric' => 'O campo :attribute deve ser um número válido.',
            'min' => 'O campo :attribute deve ser no mínimo :min.',
            'max' => 'O campo :attribute deve ser no máximo :max.',
            'array' => 'O campo :attribute deve ser uma lista válida.',
            'exists' => 'O valor informado para :attribute é inválido.',
            'in' => 'O valor informado para :attribute é inválido.',
        ], [
            'cliente_id' => 'cliente',
            'forma_pagamento' => 'forma de pagamento',
            'email_envio_fatura' => 'email para envio da fatura',
            'vencimento_servicos' => 'vencimento dos serviços',
            'itens' => 'itens do parâmetro',
            'itens.*.tipo' => 'tipo do item',
            'itens.*.nome' => 'nome do item',
            'itens.*.valor_unitario' => 'valor unitário do item',
            'itens.*.quantidade' => 'quantidade do item',
            'itens.*.valor_total' => 'valor total do item',
        ]);

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

            if (in_array($data['itens'][$idx]['tipo'] ?? '', ['EXAME', 'PACOTE_EXAMES'], true) && $servicoExameId > 0 && empty($data['itens'][$idx]['servico_id'])) {
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
        $unidadesPermitidasIds = collect($data['unidades_permitidas'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

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

        foreach ($data['itens'] as $it) {
            if (strtoupper((string) ($it['tipo'] ?? '')) === 'PACOTE_TREINAMENTOS') {
                $valorPacote = (float) ($it['valor_total'] ?? $it['valor_unitario'] ?? 0);
                if ($valorPacote <= 0) {
                    $nomePacote = $it['nome'] ?? 'Pacote de treinamentos';
                    return back()
                        ->withInput()
                        ->withErrors(['itens' => "Defina um valor para o pacote de treinamentos \"{$nomePacote}\"."]);
                }
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
        $vencimentoServicos = $data['vencimento_servicos'];

        return DB::transaction(function () use ($empresaId, $data, $valorTotal, $incluirEsocial, $valorEsocialCampo, $vencimentoServicos, $asoGrupos, $clienteAsoGrupos, $cliente, $unidadesPermitidasIds) {
            $parametro = ParametroCliente::query()
                ->where('empresa_id', $empresaId)
                ->where('cliente_id', $cliente->id)
                ->latest('id')
                ->first();

            $payload = [
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'vendedor_id' => $parametro?->vendedor_id ?? ($cliente->vendedor_id ?: auth()->id()),
                'forma_pagamento' => $data['forma_pagamento'],
                'email_envio_fatura' => !empty($data['email_envio_fatura']) ? mb_strtolower(trim((string) $data['email_envio_fatura'])) : null,
                'vencimento_servicos' => $vencimentoServicos,
                'incluir_esocial' => $incluirEsocial,
                'esocial_qtd_funcionarios' => $incluirEsocial ? ($data['esocial_qtd_funcionarios'] ?? 0) : null,
                'esocial_valor_mensal' => $incluirEsocial ? $valorEsocialCampo : 0,
                'valor_total' => $valorTotal,
            ];

            if ($parametro) {
                $parametro->update($payload);
                $parametro->itens()->delete();
            } else {
                $parametro = ParametroCliente::create($payload);
            }

            ParametroClienteAsoGrupo::query()
                ->where('parametro_cliente_id', $parametro->id)
                ->delete();

            ClienteAsoGrupo::query()
                ->where('empresa_id', $empresaId)
                ->where('cliente_id', $cliente->id)
                ->delete();

            foreach ($clienteAsoGrupos as $row) {
                ClienteAsoGrupo::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'cliente_ghe_id' => $row['cliente_ghe_id'],
                    'tipo_aso' => $row['tipo_aso'],
                    'grupo_exames_id' => $row['grupo_id'],
                    'total_exames' => $row['total_exames'],
                ]);
            }

            foreach ($asoGrupos as $row) {
                ParametroClienteAsoGrupo::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'cliente_ghe_id' => $row['cliente_ghe_id'] ?? null,
                    'parametro_cliente_id' => $parametro->id,
                    'tipo_aso' => $row['tipo_aso'],
                    'grupo_exames_id' => $row['grupo_id'],
                    'total_exames' => $row['total_exames'],
                ]);
            }

            foreach ($data['itens'] as $it) {
                ParametroClienteItem::create([
                    'parametro_cliente_id' => $parametro->id,
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

            ClienteUnidadePermitida::query()
                ->where('empresa_id', $empresaId)
                ->where('cliente_id', $cliente->id)
                ->delete();

            if (!empty($unidadesPermitidasIds)) {
                $rows = array_map(function (int $unidadeId) use ($empresaId, $cliente) {
                    return [
                        'empresa_id' => $empresaId,
                        'cliente_id' => $cliente->id,
                        'unidade_id' => $unidadeId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $unidadesPermitidasIds);

                ClienteUnidadePermitida::insert($rows);
            }

            $contrato = ClienteContrato::query()
                ->where('empresa_id', $empresaId)
                ->where('cliente_id', $cliente->id)
                ->where('status', 'ATIVO')
                ->latest('id')
                ->first();

            $usuarioNome = auth()->user()?->name ?? 'Sistema';
            $clienteNome = $cliente->razao_social ?? 'Cliente';
            $hoje = Carbon::now()->startOfDay();

            if (!$contrato) {
                $contrato = ClienteContrato::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'vendedor_id' => $cliente->vendedor_id ?: auth()->id(),
                    'proposta_id_origem' => null,
                    'parametro_cliente_id_origem' => $parametro->id,
                    'status' => 'ATIVO',
                    'vigencia_inicio' => $hoje,
                    'vigencia_fim' => null,
                    'vencimento_servicos' => $vencimentoServicos,
                    'created_by' => auth()->id(),
                ]);

                ClienteContratoLog::create([
                    'cliente_contrato_id' => $contrato->id,
                    'user_id' => auth()->id(),
                    'acao' => 'CRIACAO',
                    'descricao' => sprintf(
                        'USUARIO: %s CRIOU o contrato da empresa %s a partir do parâmetro do cliente.',
                        $usuarioNome,
                        $clienteNome
                    ),
                ]);
            } else {
                $contrato->update([
                    'parametro_cliente_id_origem' => $parametro->id,
                    'vencimento_servicos' => $vencimentoServicos,
                ]);

                ClienteContratoLog::create([
                    'cliente_contrato_id' => $contrato->id,
                    'user_id' => auth()->id(),
                    'acao' => 'MERGE_PARAMETRO',
                    'descricao' => sprintf(
                        'USUARIO: %s ATUALIZOU o contrato da empresa %s a partir do parâmetro do cliente.',
                        $usuarioNome,
                        $clienteNome
                    ),
                ]);
            }

            $contrato->load(['itens.servico', 'cliente']);

            $oldMap = [];
            foreach ($contrato->itens as $item) {
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
                ->resolveServicoAsoIdFromContrato($contrato);
            $isAsoItemContrato = function (array $it) use ($asoServicoId): bool {
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

            $temAso = collect($data['itens'])->contains(fn ($it) => $isAsoItemContrato($it));
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

            $contrato->itens()->delete();
            foreach ($data['itens'] as $it) {
                $regrasSnapshot = null;
                if ($isAsoItemContrato($it)) {
                    $regrasSnapshot = $this->buildRegrasSnapshotAso($it, $asoSnapshot);
                }

                $descricaoSnapshot = $it['descricao'] ?? $it['nome'];
                if (!empty($it['meta']['aso_tipo'])) {
                    $descricaoSnapshot = $it['nome'] ?? $it['descricao'];
                }

                ClienteContratoItem::create([
                    'cliente_contrato_id' => $contrato->id,
                    'servico_id' => $it['servico_id'] ?? null,
                    'descricao_snapshot' => $descricaoSnapshot,
                    'preco_unitario_snapshot' => $it['valor_total'] ?? $it['valor_unitario'],
                    'unidade_cobranca' => 'unidade',
                    'regras_snapshot' => $regrasSnapshot,
                    'ativo' => true,
                ]);
            }

            ClienteContratoLog::create([
                'cliente_contrato_id' => $contrato->id,
                'user_id' => auth()->id(),
                'acao' => 'ATUALIZACAO_ITENS',
                'descricao' => sprintf(
                    'USUARIO: %s ATUALIZOU os itens do contrato da empresa %s via parâmetro do cliente.',
                    $usuarioNome,
                    $clienteNome
                ),
            ]);

            foreach ($oldMap as $key => $oldItem) {
                if (!array_key_exists($key, $newMap)) {
                    ClienteContratoLog::create([
                        'cliente_contrato_id' => $contrato->id,
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
                        'cliente_contrato_id' => $contrato->id,
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
                        'cliente_contrato_id' => $contrato->id,
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

            return back()->with('ok', 'Parâmetros do cliente atualizados com sucesso.');
        });
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

    private function authorizeCliente(Cliente $cliente): void
    {
        $empresaId = auth()->user()->empresa_id ?? 1;
        if ($cliente->empresa_id != $empresaId) {
            abort(403);
        }
    }

    private function routeName(string $suffix): string
    {
        return request()->routeIs('comercial.clientes.*')
            ? 'comercial.clientes.' . $suffix
            : 'clientes.' . $suffix;
    }
}
