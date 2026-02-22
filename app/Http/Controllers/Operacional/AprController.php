<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Operacional\Concerns\ValidatesClientePortalTaskEditing;
use App\Http\Controllers\Controller;
use App\Models\AprSolicitacoes;
use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\Estado;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Services\TempoTarefaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AprController extends Controller
{
    use ValidatesClientePortalTaskEditing;

    public function create(Cliente $cliente)
    {
        $user = auth()->user();
        $empresaId = (int) $user->empresa_id;
        abort_if($cliente->empresa_id !== $empresaId, 403);

        return view('operacional.kanban.apr.create', [
            'cliente' => $cliente,
            'apr'     => null,
            'isEdit'  => false,
            'estados' => Estado::query()->orderBy('uf')->get(['uf', 'nome']),
            'colaboradores' => $this->colaboradoresDoCliente($empresaId, (int) $cliente->id),
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $aprovando = $this->isAprovando($request);
        $data = $request->validate(
            $this->regrasValidacao($aprovando),
            $this->mensagensValidacao(),
            $this->atributosValidacao()
        );

        $etapas = $this->normalizarEtapas($data['etapas'] ?? []);
        $equipe = $this->normalizarEquipe($data['equipe'] ?? []);
        $epis = $this->normalizarEpis($data['epis'] ?? []);

        // coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // serviço APR
        $servicoApr = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'APR')
            ->first();

        DB::transaction(function () use (
            $data,
            $empresaId,
            $cliente,
            $user,
            $colunaInicial,
            $servicoApr,
            $etapas,
            $equipe,
            $epis,
            $aprovando
        ) {
            $inicioPrevisto = now();
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, optional($servicoApr)->id);

            $enderecoCard = trim((string) ($data['obra_endereco'] ?? $data['endereco_atividade'] ?? ''));
            $obraNome = trim((string) ($data['obra_nome'] ?? ''));

            $tarefa = Tarefa::create([
                'empresa_id'      => $empresaId,
                'cliente_id'      => $cliente->id,
                'responsavel_id'  => $user->id,
                'coluna_id'       => optional($colunaInicial)->id,
                'servico_id'      => optional($servicoApr)->id,
                'titulo'          => $obraNome !== '' ? 'APR - ' . $obraNome : 'APR - Análise Preliminar de Riscos',
                'descricao'       => $aprovando ? 'APR aprovada e enviada para execução.' : 'APR salva como rascunho.',
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto'    => $fimPrevisto,
            ]);

            AprSolicitacoes::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
                'responsavel_id' => $user->id,

                'contratante_razao_social' => $data['contratante_razao_social'] ?? null,
                'contratante_cnpj' => $data['contratante_cnpj'] ?? null,
                'contratante_responsavel_nome' => $data['contratante_responsavel_nome'] ?? null,
                'contratante_telefone' => $data['contratante_telefone'] ?? null,
                'contratante_email' => $data['contratante_email'] ?? null,

                'obra_nome' => $data['obra_nome'] ?? null,
                'obra_endereco' => $data['obra_endereco'] ?? null,
                'obra_cidade' => $data['obra_cidade'] ?? null,
                'obra_uf' => isset($data['obra_uf']) ? strtoupper((string) $data['obra_uf']) : null,
                'obra_cep' => $data['obra_cep'] ?? null,
                'obra_area_setor' => $data['obra_area_setor'] ?? null,

                'atividade_descricao' => $data['atividade_descricao'] ?? null,
                'atividade_data_inicio' => $data['atividade_data_inicio'] ?? null,
                'atividade_data_termino_prevista' => $data['atividade_data_termino_prevista'] ?? null,

                'etapas_json' => $etapas,
                'equipe_json' => $equipe,
                'epis_json' => $epis,

                'status' => $aprovando ? 'aprovada' : 'rascunho',
                'aprovada_em' => $aprovando ? now() : null,
                'aprovada_por' => $aprovando ? $user->id : null,

                // Compatibilidade com telas antigas
                'endereco_atividade' => $enderecoCard !== '' ? $enderecoCard : null,
                'funcoes_envolvidas' => $this->serializarFuncoesLegadas($equipe),
                'etapas_atividade' => $this->serializarEtapasLegadas($etapas),
            ]);

            TarefaLog::create([
                'tarefa_id'      => $tarefa->id,
                'user_id'        => $user->id,
                'de_coluna_id'   => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao'           => 'criado',
                'observacao'     => $aprovando
                    ? 'Tarefa APR criada e aprovada pelo usuário.'
                    : 'Tarefa APR criada como rascunho pelo usuário.',
            ]);
        });

        $origem = $request->query('origem', $request->input('origem'));
        if ($origem === 'cliente' || $user->isCliente()) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', $aprovando
                    ? 'APR criada com sucesso e enviada para execução.'
                    : 'Rascunho de APR salvo com sucesso.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', $aprovando
                ? 'APR criada com sucesso.'
                : 'Rascunho de APR salvo com sucesso.');
    }

    /**
     * Editar APR a partir da tarefa do Kanban
     */
    public function edit(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $apr = AprSolicitacoes::where('tarefa_id', $tarefa->id)->firstOrFail();
        $cliente = $apr->cliente;

        return view('operacional.kanban.apr.create', [
            'cliente' => $cliente,
            'apr'     => $apr,
            'isEdit'  => true,
            'estados' => Estado::query()->orderBy('uf')->get(['uf', 'nome']),
            'colaboradores' => $this->colaboradoresDoCliente($empresaId, (int) $cliente->id),
        ]);
    }

    /**
     * Update APR
     */
    public function update(AprSolicitacoes $apr, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $apr->tarefa)) {
            return $redirect;
        }

        $user      = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($apr->empresa_id !== $empresaId, 403);

        $aprovando = $this->isAprovando($request);
        $data = $request->validate(
            $this->regrasValidacao($aprovando),
            $this->mensagensValidacao(),
            $this->atributosValidacao()
        );

        $etapas = $this->normalizarEtapas($data['etapas'] ?? []);
        $equipe = $this->normalizarEquipe($data['equipe'] ?? []);
        $epis = $this->normalizarEpis($data['epis'] ?? []);

        DB::transaction(function () use ($apr, $data, $user, $etapas, $equipe, $epis, $aprovando) {
            $enderecoCard = trim((string) ($data['obra_endereco'] ?? $data['endereco_atividade'] ?? ''));
            $obraNome = trim((string) ($data['obra_nome'] ?? ''));

            $apr->update([
                'contratante_razao_social' => $data['contratante_razao_social'] ?? null,
                'contratante_cnpj' => $data['contratante_cnpj'] ?? null,
                'contratante_responsavel_nome' => $data['contratante_responsavel_nome'] ?? null,
                'contratante_telefone' => $data['contratante_telefone'] ?? null,
                'contratante_email' => $data['contratante_email'] ?? null,

                'obra_nome' => $data['obra_nome'] ?? null,
                'obra_endereco' => $data['obra_endereco'] ?? null,
                'obra_cidade' => $data['obra_cidade'] ?? null,
                'obra_uf' => isset($data['obra_uf']) ? strtoupper((string) $data['obra_uf']) : null,
                'obra_cep' => $data['obra_cep'] ?? null,
                'obra_area_setor' => $data['obra_area_setor'] ?? null,

                'atividade_descricao' => $data['atividade_descricao'] ?? null,
                'atividade_data_inicio' => $data['atividade_data_inicio'] ?? null,
                'atividade_data_termino_prevista' => $data['atividade_data_termino_prevista'] ?? null,

                'etapas_json' => $etapas,
                'equipe_json' => $equipe,
                'epis_json' => $epis,

                'status' => $aprovando ? 'aprovada' : 'rascunho',
                'aprovada_em' => $aprovando ? now() : null,
                'aprovada_por' => $aprovando ? $user->id : null,

                // Compatibilidade com telas antigas
                'endereco_atividade' => $enderecoCard !== '' ? $enderecoCard : null,
                'funcoes_envolvidas' => $this->serializarFuncoesLegadas($equipe),
                'etapas_atividade' => $this->serializarEtapasLegadas($etapas),
            ]);

            if ($apr->tarefa) {
                $apr->tarefa->update([
                    'titulo' => $obraNome !== '' ? 'APR - ' . $obraNome : 'APR - Análise Preliminar de Riscos',
                    'descricao' => $aprovando ? 'APR aprovada e enviada para execução.' : 'APR salva como rascunho.',
                ]);

                TarefaLog::create([
                    'tarefa_id'      => $apr->tarefa_id,
                    'user_id'        => $user->id,
                    'de_coluna_id'   => $apr->tarefa->coluna_id,
                    'para_coluna_id' => $apr->tarefa->coluna_id,
                    'acao'           => 'atualizado',
                    'observacao'     => $aprovando
                        ? 'APR atualizada e aprovada pelo usuário.'
                        : 'APR atualizada e salva como rascunho pelo usuário.',
                ]);
            }
        });

        $origem = $request->query('origem', $request->input('origem'));
        if ($origem === 'cliente' || $user->isCliente()) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', $aprovando
                    ? 'APR atualizada com sucesso.'
                    : 'Rascunho de APR atualizado com sucesso.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', $aprovando
                ? 'APR atualizada com sucesso.'
                : 'Rascunho de APR atualizado com sucesso.');
    }

    private function isAprovando(Request $request): bool
    {
        return (string) $request->input('acao', 'aprovar') === 'aprovar';
    }

    private function regrasValidacao(bool $aprovando): array
    {
        $regraTextoObrigatorio = $aprovando
            ? ['required', 'string']
            : ['nullable', 'string'];
        $regraCnpj = function (string $attribute, mixed $value, \Closure $fail) {
            $valor = trim((string) ($value ?? ''));

            if ($valor === '') {
                return;
            }

            if (!$this->cnpjValido($valor)) {
                $fail('O campo cnpj da contratante e invalido.');
            }
        };

        return [
            'contratante_razao_social' => array_merge($regraTextoObrigatorio, ['max:255']),
            'contratante_cnpj' => $aprovando
                ? ['required', 'string', 'max:20', $regraCnpj]
                : ['nullable', 'string', 'max:20', $regraCnpj],
            'contratante_responsavel_nome' => ['nullable', 'string', 'max:255'],
            'contratante_telefone' => ['nullable', 'string', 'max:25'],
            'contratante_email' => ['nullable', 'email', 'max:255'],

            'obra_nome' => array_merge($regraTextoObrigatorio, ['max:255']),
            'obra_endereco' => array_merge($regraTextoObrigatorio, ['max:255']),
            'obra_cidade' => array_merge($regraTextoObrigatorio, ['max:100']),
            'obra_uf' => $aprovando
                ? ['required', 'string', 'size:2']
                : ['nullable', 'string', 'size:2'],
            'obra_cep' => ['nullable', 'string', 'max:10'],
            'obra_area_setor' => ['nullable', 'string', 'max:120'],

            'atividade_descricao' => $regraTextoObrigatorio,
            'atividade_data_inicio' => $aprovando
                ? ['required', 'date']
                : ['nullable', 'date'],
            'atividade_data_termino_prevista' => $aprovando
                ? ['required', 'date', 'after_or_equal:atividade_data_inicio']
                : ['nullable', 'date', 'after_or_equal:atividade_data_inicio'],

            'etapas' => $aprovando
                ? ['required', 'array', 'min:3']
                : ['nullable', 'array'],
            'etapas.*.descricao' => $aprovando
                ? ['required', 'string', 'max:500']
                : ['nullable', 'string', 'max:500'],

            'equipe' => $aprovando
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'],
            'equipe.*.nome' => $aprovando
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'equipe.*.funcao' => $aprovando
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'epis' => $aprovando
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'],
            'epis.*.tipo' => ['nullable', 'in:epi,maquina'],
            'epis.*.descricao' => $aprovando
                ? ['required', 'string', 'max:255']
                : ['nullable', 'string', 'max:255'],
            'epis.*.aplicacao' => ['nullable', 'string', 'max:255'],

            'acao' => ['nullable', 'in:rascunho,aprovar'],
        ];
    }

    private function normalizarEtapas(array $etapas): array
    {
        return collect($etapas)
            ->map(function ($item) {
                return [
                    'descricao' => trim((string) ($item['descricao'] ?? '')),
                ];
            })
            ->filter(fn ($item) => $item['descricao'] !== '')
            ->values()
            ->all();
    }

    private function normalizarEquipe(array $equipe): array
    {
        return collect($equipe)
            ->map(function ($item) {
                return [
                    'nome' => trim((string) ($item['nome'] ?? '')),
                    'funcao' => trim((string) ($item['funcao'] ?? '')),
                ];
            })
            ->filter(fn ($item) => $item['nome'] !== '' || $item['funcao'] !== '')
            ->values()
            ->all();
    }

    private function normalizarEpis(array $epis): array
    {
        return collect($epis)
            ->map(function ($item) {
                $tipo = (string) ($item['tipo'] ?? 'epi');
                if (!in_array($tipo, ['epi', 'maquina'], true)) {
                    $tipo = 'epi';
                }

                return [
                    'tipo' => $tipo,
                    'descricao' => trim((string) ($item['descricao'] ?? '')),
                    'aplicacao' => trim((string) ($item['aplicacao'] ?? '')),
                ];
            })
            ->filter(fn ($item) => $item['descricao'] !== '')
            ->values()
            ->all();
    }

    private function serializarFuncoesLegadas(array $equipe): ?string
    {
        $linhas = collect($equipe)
            ->map(function ($item) {
                $nome = trim((string) ($item['nome'] ?? ''));
                $funcao = trim((string) ($item['funcao'] ?? ''));

                if ($nome !== '' && $funcao !== '') {
                    return $nome . ' - ' . $funcao;
                }

                return $nome !== '' ? $nome : $funcao;
            })
            ->filter()
            ->values();

        return $linhas->isNotEmpty() ? $linhas->implode('; ') : null;
    }

    private function serializarEtapasLegadas(array $etapas): ?string
    {
        $linhas = collect($etapas)
            ->pluck('descricao')
            ->filter()
            ->values();

        return $linhas->isNotEmpty() ? $linhas->implode("\n") : null;
    }

    private function mensagensValidacao(): array
    {
        return [
            'required' => 'O campo :attribute e obrigatorio.',
            'string' => 'O campo :attribute deve ser um texto valido.',
            'max' => 'O campo :attribute deve ter no maximo :max caracteres.',
            'array' => 'O campo :attribute deve ser uma lista valida.',
            'min' => 'O campo :attribute deve ter pelo menos :min item(ns).',
            'in' => 'O valor informado em :attribute e invalido.',
            'email' => 'O campo :attribute deve ser um e-mail valido.',
            'date' => 'O campo :attribute deve conter uma data valida.',
            'after_or_equal' => 'A data de termino deve ser maior ou igual a data de inicio.',
            'size' => 'O campo :attribute deve ter :size caracteres.',
            'boolean' => 'O campo :attribute deve ser verdadeiro ou falso.',
        ];
    }

    private function cnpjValido(string $valor): bool
    {
        $cnpj = preg_replace('/\D+/', '', $valor) ?? '';

        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $calcularDigito = function (string $base): int {
            $peso = strlen($base) - 7;
            $soma = 0;

            for ($i = 0; $i < strlen($base); $i++) {
                $soma += ((int) $base[$i]) * $peso;
                $peso--;
                if ($peso < 2) {
                    $peso = 9;
                }
            }

            $resto = $soma % 11;
            return $resto < 2 ? 0 : 11 - $resto;
        };

        $base12 = substr($cnpj, 0, 12);
        $dig1 = $calcularDigito($base12);
        if ($dig1 !== (int) $cnpj[12]) {
            return false;
        }

        $base13 = substr($cnpj, 0, 13);
        $dig2 = $calcularDigito($base13);
        return $dig2 === (int) $cnpj[13];
    }

    private function atributosValidacao(): array
    {
        return [
            'contratante_razao_social' => 'razao social da contratante',
            'contratante_cnpj' => 'cnpj da contratante',
            'contratante_responsavel_nome' => 'nome do responsavel',
            'contratante_telefone' => 'telefone da contratante',
            'contratante_email' => 'e-mail da contratante',
            'obra_nome' => 'nome da obra',
            'obra_endereco' => 'endereco da obra',
            'obra_cidade' => 'cidade da obra',
            'obra_uf' => 'uf da obra',
            'obra_cep' => 'cep da obra',
            'obra_area_setor' => 'area/setor da atividade',
            'atividade_descricao' => 'descricao da atividade',
            'atividade_data_inicio' => 'data de inicio',
            'atividade_data_termino_prevista' => 'previsao de termino',
            'etapas' => 'etapas da atividade',
            'etapas.*.descricao' => 'descricao da etapa',
            'equipe' => 'equipe envolvida',
            'equipe.*.nome' => 'nome do trabalhador',
            'equipe.*.funcao' => 'funcao do trabalhador',
            'epis' => 'epis',
            'epis.*.tipo' => 'tipo do item',
            'epis.*.descricao' => 'descricao do epi',
            'epis.*.aplicacao' => 'aplicacao do epi',
            'acao' => 'acao do formulario',
        ];
    }

    private function colaboradoresDoCliente(int $empresaId, int $clienteId): array
    {
        return Funcionario::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->where(function ($q) {
                $q->whereNull('ativo')->orWhere('ativo', true);
            })
            ->with(['funcao:id,nome'])
            ->orderBy('nome')
            ->get(['id', 'nome', 'funcao_id'])
            ->map(function ($funcionario) {
                return [
                    'nome' => (string) $funcionario->nome,
                    'funcao' => (string) optional($funcionario->funcao)->nome,
                ];
            })
            ->filter(fn ($item) => trim($item['nome']) !== '')
            ->values()
            ->all();
    }
}
