<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Operacional\Concerns\ValidatesClientePortalTaskEditing;
use App\Models\Cliente;
use App\Models\ClienteUnidadePermitida;
use App\Models\ExameToxicologicoSolicitacao;
use App\Models\Funcionario;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use App\Models\UnidadeClinica;
use App\Services\TempoTarefaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExameToxicologicoController extends Controller
{
    use ValidatesClientePortalTaskEditing;

    public function create(Cliente $cliente)
    {
        $user = auth()->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        return view('operacional.kanban.exame-toxicologico.create', [
            'cliente' => $cliente,
            'solicitacao' => null,
            'isEdit' => false,
            'tiposExame' => $this->tiposExame(),
            'unidades' => $this->unidadesDisponiveis($empresaId, $cliente->id),
            'funcionarios' => $this->funcionariosDoCliente($empresaId, $cliente->id),
        ]);
    }

    public function store(Cliente $cliente, Request $request)
    {
        $user = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $this->validateData($request, $empresaId, $cliente->id);
        $data = $this->mergeFuncionarioSnapshot($data, $empresaId, $cliente->id);

        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        $servico = $this->resolveServico($empresaId);

        DB::transaction(function () use ($empresaId, $cliente, $user, $data, $colunaInicial, $servico) {
            $inicioPrevisto = now();
            $fimPrevisto = app(TempoTarefaService::class)
                ->calcularFimPrevisto($inicioPrevisto, $empresaId, optional($servico)->id);

            $solicitanteLabel = ($data['solicitacao_para'] ?? 'independente') === 'funcionario'
                ? 'Colaborador da empresa'
                : 'Independente';
            $titulo = 'Exame toxicológico - ' . $solicitanteLabel . ' - ' . $data['nome_completo'];
            $descricao = sprintf(
                'Solicitante: %s | Tipo: %s | Data: %s | Unidade: %s',
                $solicitanteLabel,
                $this->tiposExame()[$data['tipo_exame']] ?? $data['tipo_exame'],
                $data['data_realizacao'],
                optional($this->resolveUnidade($empresaId, (int) $data['unidade_id']))->nome ?? 'Não informada'
            );

            $tarefa = Tarefa::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'responsavel_id' => $user->id,
                'coluna_id' => optional($colunaInicial)->id,
                'servico_id' => optional($servico)->id,
                'titulo' => $titulo,
                'descricao' => $descricao,
                'inicio_previsto' => $inicioPrevisto,
                'fim_previsto' => $fimPrevisto,
            ]);

            ExameToxicologicoSolicitacao::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
                'funcionario_id' => $data['funcionario_id'] ?? null,
                'responsavel_id' => $user->id,
                'unidade_id' => $data['unidade_id'],
                'tipo_exame' => $data['tipo_exame'],
                'nome_completo' => $data['nome_completo'],
                'cpf' => $data['cpf'],
                'rg' => $data['rg'],
                'data_nascimento' => $data['data_nascimento'],
                'telefone' => $data['telefone'],
                'email_envio' => $data['email_envio'],
                'data_realizacao' => $data['data_realizacao'],
            ]);

            TarefaLog::create([
                'tarefa_id' => $tarefa->id,
                'user_id' => $user->id,
                'de_coluna_id' => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao' => 'criado',
                'observacao' => 'Tarefa de exame toxicológico criada pelo usuário.',
            ]);
        });

        $origem = $request->query('origem', $request->input('origem'));
        if ($origem === 'cliente' || $user->isCliente()) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'Solicitação de exame toxicológico criada com sucesso.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa de exame toxicológico criada com sucesso.');
    }

    public function edit(Tarefa $tarefa, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $tarefa)) {
            return $redirect;
        }

        $user = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $solicitacao = ExameToxicologicoSolicitacao::where('tarefa_id', $tarefa->id)->firstOrFail();

        return view('operacional.kanban.exame-toxicologico.create', [
            'cliente' => $solicitacao->cliente,
            'solicitacao' => $solicitacao,
            'isEdit' => true,
            'tiposExame' => $this->tiposExame(),
            'unidades' => $this->unidadesDisponiveis($empresaId, $solicitacao->cliente_id, $solicitacao->unidade_id),
            'funcionarios' => $this->funcionariosDoCliente($empresaId, $solicitacao->cliente_id),
        ]);
    }

    public function update(ExameToxicologicoSolicitacao $exameToxicologico, Request $request)
    {
        if ($redirect = $this->ensureClientePodeEditarTarefa($request, $exameToxicologico->tarefa)) {
            return $redirect;
        }

        $user = $request->user();
        $empresaId = $user->empresa_id;

        abort_if($exameToxicologico->empresa_id !== $empresaId, 403);

        $data = $this->validateData($request, $empresaId, $exameToxicologico->cliente_id);
        $data = $this->mergeFuncionarioSnapshot($data, $empresaId, $exameToxicologico->cliente_id);

        DB::transaction(function () use ($exameToxicologico, $data, $user, $empresaId) {
            $exameToxicologico->update($data);

            if ($exameToxicologico->tarefa) {
                $unidadeNome = optional($this->resolveUnidade($empresaId, (int) $data['unidade_id']))->nome ?? 'Não informada';
                $solicitanteLabel = ($data['solicitacao_para'] ?? 'independente') === 'funcionario'
                    ? 'Colaborador da empresa'
                    : 'Independente';
                $exameToxicologico->tarefa->update([
                    'titulo' => 'Exame toxicológico - ' . $solicitanteLabel . ' - ' . $data['nome_completo'],
                    'descricao' => sprintf(
                        'Solicitante: %s | Tipo: %s | Data: %s | Unidade: %s',
                        $solicitanteLabel,
                        $this->tiposExame()[$data['tipo_exame']] ?? $data['tipo_exame'],
                        $data['data_realizacao'],
                        $unidadeNome
                    ),
                ]);

                TarefaLog::create([
                    'tarefa_id' => $exameToxicologico->tarefa_id,
                    'user_id' => $user->id,
                    'de_coluna_id' => $exameToxicologico->tarefa->coluna_id,
                    'para_coluna_id' => $exameToxicologico->tarefa->coluna_id,
                    'acao' => 'atualizado',
                    'observacao' => 'Exame toxicológico atualizado pelo usuário.',
                ]);
            }
        });

        $origem = $request->query('origem', $request->input('origem'));
        if ($origem === 'cliente' || $user->isCliente()) {
            return redirect()
                ->route('cliente.agendamentos')
                ->with('ok', 'Exame toxicológico atualizado com sucesso.');
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Exame toxicológico atualizado com sucesso.');
    }

    private function validateData(Request $request, int $empresaId, int $clienteId): array
    {
        $unidadesPermitidasIds = $this->unidadesPermitidasIds($empresaId, $clienteId);

        return $request->validate([
            'tipo_exame' => ['required', Rule::in(array_keys($this->tiposExame()))],
            'solicitacao_para' => ['required', Rule::in(['funcionario', 'independente'])],
            'funcionario_id' => ['nullable', 'integer', 'required_if:solicitacao_para,funcionario'],
            'nome_completo' => ['required_if:solicitacao_para,independente', 'nullable', 'string', 'max:255'],
            'cpf' => ['required_if:solicitacao_para,independente', 'nullable', 'string', 'max:20'],
            'rg' => ['required_if:solicitacao_para,independente', 'nullable', 'string', 'max:30'],
            'data_nascimento' => ['required_if:solicitacao_para,independente', 'nullable', 'date_format:Y-m-d'],
            'telefone' => ['required_if:solicitacao_para,independente', 'nullable', 'string', 'max:30'],
            'email_envio' => ['required', 'email', 'max:255'],
            'data_realizacao' => ['required', 'date_format:Y-m-d'],
            'unidade_id' => ['required', 'integer', Rule::in($unidadesPermitidasIds)],
        ], [
            'tipo_exame.required' => 'Selecione o tipo de exame toxicológico.',
            'solicitacao_para.required' => 'Selecione se a solicitação é para colaborador da empresa ou independente.',
            'funcionario_id.required_if' => 'Selecione o colaborador da empresa.',
            'nome_completo.required' => 'Informe o nome completo.',
            'nome_completo.required_if' => 'Informe o nome completo.',
            'cpf.required' => 'Informe o CPF.',
            'cpf.required_if' => 'Informe o CPF.',
            'rg.required' => 'Informe o RG.',
            'rg.required_if' => 'Informe o RG.',
            'data_nascimento.required' => 'Informe a data de nascimento.',
            'data_nascimento.required_if' => 'Informe a data de nascimento.',
            'data_nascimento.date_format' => 'Informe uma data de nascimento válida.',
            'telefone.required' => 'Informe o telefone.',
            'telefone.required_if' => 'Informe o telefone.',
            'email_envio.required' => 'Informe o e-mail para envio do exame.',
            'email_envio.email' => 'Informe um e-mail válido para envio do exame.',
            'data_realizacao.required' => 'Informe a data de realização.',
            'data_realizacao.date_format' => 'Informe uma data de realização válida.',
            'unidade_id.required' => 'Selecione a unidade.',
            'unidade_id.in' => 'Selecione uma unidade permitida para este cliente.',
        ], [
            'tipo_exame' => 'tipo de exame toxicológico',
            'solicitacao_para' => 'tipo de solicitante',
            'funcionario_id' => 'colaborador da empresa',
            'nome_completo' => 'nome completo',
            'cpf' => 'CPF',
            'rg' => 'RG',
            'data_nascimento' => 'data de nascimento',
            'telefone' => 'telefone',
            'email_envio' => 'e-mail para envio do exame',
            'data_realizacao' => 'data de realização',
            'unidade_id' => 'unidade',
        ]);
    }

    private function mergeFuncionarioSnapshot(array $data, int $empresaId, int $clienteId): array
    {
        if (($data['solicitacao_para'] ?? null) !== 'funcionario') {
            $data['funcionario_id'] = null;
            return $data;
        }

        $funcionarioId = (int) ($data['funcionario_id'] ?? 0);
        $funcionario = Funcionario::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->find($funcionarioId);

        if (!$funcionario) {
            throw ValidationException::withMessages([
                'funcionario_id' => 'Selecione um colaborador válido.',
            ]);
        }

        $faltantes = [];
        if (blank($funcionario->nome)) $faltantes[] = 'nome';
        if (blank($funcionario->cpf)) $faltantes[] = 'CPF';
        if (blank($funcionario->rg)) $faltantes[] = 'RG';
        if (!$funcionario->data_nascimento) $faltantes[] = 'data de nascimento';
        if (!empty($faltantes)) {
            throw ValidationException::withMessages([
                'funcionario_id' => 'O colaborador selecionado não possui cadastro completo: ' . implode(', ', $faltantes) . '.',
            ]);
        }

        $data['funcionario_id'] = (int) $funcionario->id;
        $data['nome_completo'] = (string) $funcionario->nome;
        $data['cpf'] = (string) $funcionario->cpf;
        $data['rg'] = (string) $funcionario->rg;
        $data['data_nascimento'] = $funcionario->data_nascimento?->format('Y-m-d');
        $data['telefone'] = (string) ($funcionario->celular ?? '');

        return $data;
    }

    private function tiposExame(): array
    {
        return [
            'clt' => 'CLT',
            'cnh' => 'CNH',
            'concurso_publico' => 'Concurso Público',
        ];
    }

    private function resolveServico(int $empresaId): ?Servico
    {
        return Servico::query()
            ->where('empresa_id', $empresaId)
            ->where(function ($query) {
                $query->where('nome', 'Exame toxicológico')
                    ->orWhere('nome', 'Exame toxicologico');
            })
            ->first();
    }

    private function resolveUnidade(int $empresaId, int $unidadeId): ?UnidadeClinica
    {
        return UnidadeClinica::query()
            ->where('empresa_id', $empresaId)
            ->where('id', $unidadeId)
            ->first();
    }

    private function funcionariosDoCliente(int $empresaId, int $clienteId)
    {
        return Funcionario::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->orderBy('nome')
            ->get(['id', 'nome', 'cpf', 'rg', 'data_nascimento', 'celular']);
    }

    private function unidadesDisponiveis(int $empresaId, int $clienteId, ?int $incluirUnidadeId = null)
    {
        $permitidasIds = ClienteUnidadePermitida::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->pluck('unidade_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $query = UnidadeClinica::query()->where('empresa_id', $empresaId);
        if (!empty($permitidasIds)) {
            $query->whereIn('id', $permitidasIds);
        }

        $unidades = $query->orderBy('nome')->get();

        if ($incluirUnidadeId && !$unidades->contains(fn ($unidade) => (int) $unidade->id === (int) $incluirUnidadeId)) {
            $extra = $this->resolveUnidade($empresaId, $incluirUnidadeId);
            if ($extra) {
                $unidades->push($extra);
                $unidades = $unidades->sortBy('nome')->values();
            }
        }

        return $unidades;
    }

    private function unidadesPermitidasIds(int $empresaId, int $clienteId): array
    {
        $permitidasIds = ClienteUnidadePermitida::query()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->pluck('unidade_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (!empty($permitidasIds)) {
            return $permitidasIds;
        }

        return UnidadeClinica::query()
            ->where('empresa_id', $empresaId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }
}
