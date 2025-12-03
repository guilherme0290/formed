<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Anexos;
use App\Models\AsoSolicitacoes;
use App\Models\Cliente;
use App\Models\Funcao;
use App\Models\Funcionario;
use App\Models\KanbanColuna;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsoController extends Controller
{
    public function asoStore(Cliente $cliente, Request $request)
    {
        $usuario = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $data = $request->validate([
            'funcionario_id' => ['nullable', 'exists:funcionarios,id'],
            'nome' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'rg' => ['nullable', 'string', 'max:255'],
            'funcao_id' => ['nullable', 'integer', 'exists:funcoes,id'],
            'tipo_aso' => ['required', 'in:admissional,periodico,demissional,mudanca_funcao,retorno_trabalho'],
            'data_aso' => ['required', 'date_format:Y-m-d'],
            'unidade_id' => ['required', 'exists:unidades_clinicas,id'],
            'vai_fazer_treinamento' => ['nullable', 'boolean'],
            'treinamentos' => ['array'],
            'treinamentos.*' => ['string'],
            'email_aso' => ['nullable', 'email'],
        ]);

        $tarefa = DB::transaction(function () use ($data, $empresaId, $cliente, $usuario,$request) {

            // 1) Resolve funcionário (existente ou novo)
            if (!empty($data['funcionario_id'])) {
                $funcionario = Funcionario::where('empresa_id', $empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->with('funcao')
                    ->findOrFail($data['funcionario_id']);
            } else {
                $funcionario = Funcionario::create([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                    'nome' => $data['nome'],
                    'cpf' => $data['cpf'] ?? null,
                    'rg' => $data['rg'],
                    'data_nascimento' => $data['data_nascimento'],
                    'funcao_id' => $data['funcao_id'] ?? null,
                ]);
            }

            // 2) Coluna inicial do Kanban
            $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
                ->where('slug', 'pendente')
                ->first()
                ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

            $servicoAsoId = Servico::where('nome', 'ASO')->value('id');

            // 3) Monta título e descrição "humana" (mas agora só pra exibir)
            $tipoAsoLabel = match ($data['tipo_aso']) {
                'admissional' => 'Admissional',
                'periodico' => 'Periódico',
                'demissional' => 'Demissional',
                'mudanca_funcao' => 'Mudança de Função',
                'retorno_trabalho' => 'Retorno ao Trabalho',
            };

            $titulo = "ASO - {$funcionario->nome}";
            $descricao = "Tipo: {$tipoAsoLabel}";

            if (
                $data['tipo_aso'] === 'mudanca_funcao'
                && !empty($data['funcionario_id'])
                && !empty($data['funcao_id'])
            ) {
                $funcaoAnteriorNome = optional($funcionario->funcao)->nome ?? 'Não informada';
                $funcionario->funcao_id = $data['funcao_id'];
                $funcionario->save();

                $funcaoNovaNome = Funcao::find($data['funcao_id'])->nome ?? 'Não informada';
                $descricao .= " | Mudança de função: {$funcaoAnteriorNome} → {$funcaoNovaNome}";
            }

            if (!empty($data['vai_fazer_treinamento']) && !empty($data['treinamentos'])) {
                $descricao .= ' | Treinamentos: ' . implode(', ', $data['treinamentos']);
            }

            // 4) Cria a tarefa
            $tarefa = Tarefa::create([
                'empresa_id' => $empresaId,
                'coluna_id' => optional($colunaInicial)->id,
                'cliente_id' => $cliente->id,
                'responsavel_id' => $usuario->id,
                'funcionario_id' => $funcionario->id,
                'servico_id' => $servicoAsoId,
                'titulo' => $titulo,
                'descricao' => $descricao,
                'inicio_previsto' => $data['data_aso'],
            ]);

            // 5) Cria o registro específico de ASO
            AsoSolicitacoes::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
                'funcionario_id' => $funcionario->id,
                'unidade_id' => $data['unidade_id'],
                'tipo_aso' => $data['tipo_aso'],
                'data_aso' => $data['data_aso'],
                'email_aso' => $data['email_aso'] ?? null,
                'vai_fazer_treinamento' => !empty($data['vai_fazer_treinamento']),
                'treinamentos' => $data['treinamentos'] ?? [],
            ]);

            // 6) Log inicial
            TarefaLog::create([
                'tarefa_id' => $tarefa->id,
                'user_id' => $usuario->id,
                'de_coluna_id' => null,
                'para_coluna_id' => optional($colunaInicial)->id,
                'acao' => 'criado',
                'observacao' => $descricao,
            ]);

            Anexos::salvarDoRequest($request, 'anexos', [
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'tarefa_id'      => $tarefa->id,
                'funcionario_id' => $funcionario->id ?? null,
                'uploaded_by'    => $usuario->id,
                'servico'        => 'ASO',
                 'subpath'     => 'anexos-custom/' . $empresaId, // opcional, se quiser sobrescrever
            ]);

            return $tarefa;
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', "Tarefa ASO criada para o colaborador {$tarefa->titulo}.");
    }

    public function edit(Tarefa $tarefa)
    {
        $empresaId = Auth::user()->empresa_id;
        $cliente = $tarefa->cliente;

        $anexos = $tarefa->anexos()
            ->orderByDesc('created_at')
            ->get();

        $tiposAso = [
            'admissional' => 'Admissional',
            'periodico' => 'Periódico',
            'demissional' => 'Demissional',
            'mudanca_funcao' => 'Mudança de Função',
            'retorno_trabalho' => 'Retorno ao Trabalho',
        ];

        $funcionarios = Funcionario::where('cliente_id', $cliente->id)
            ->orderBy('nome')
            ->get();

        $unidades = \App\Models\UnidadeClinica::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        $funcoes = Funcao::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        $treinamentosDisponiveis = [
            'nr_35' => 'NR-35 - Trabalho em Altura',
            'nr_18' => 'NR-18 - Integração',
            'nr_12' => 'NR-12 - Máquinas e Equipamentos',
            'nr_06' => 'NR-06 - EPI',
            'nr_05' => 'NR-05 - CIPA Designada',
            'nr_01' => 'NR-01 - Ordem de Serviço',
            'nr_33' => 'NR-33 - Espaço Confinado',
            'nr_11' => 'NR-11 - Movimentação de Carga',
            'nr_10' => 'NR-10 - Elétrica'
        ];

        $aso = $tarefa->asoSolicitacao; // pode ser null para tarefas antigas

        // DATA
        $dataAso = old('data_aso');

        if (!$dataAso) {
            if ($aso && $aso->data_aso) {
                $dataAso = $aso->data_aso->format('Y-m-d');
            } elseif ($tarefa->inicio_previsto) {
                $dataAso = Carbon::parse($tarefa->inicio_previsto)->format('Y-m-d');
            }
        }

        // UNIDADE
        $unidadeSelecionada = old('unidade_id');

        if (!$unidadeSelecionada) {
            if ($aso) {
                $unidadeSelecionada = $aso->unidade_id;
            } elseif ($tarefa->descricao && preg_match('/Unidade ID:\s*(\d+)/', $tarefa->descricao, $m)) {
                $unidadeSelecionada = $m[1];
            }
        }

        // TREINAMENTOS
        $treinamentosSelecionados = old('treinamentos', []);

        if (empty($treinamentosSelecionados)) {
            if ($aso && !empty($aso->treinamentos)) {
                $treinamentosSelecionados = $aso->treinamentos;
            } elseif ($tarefa->descricao && preg_match('/Treinamentos:\s*(.+)$/i', $tarefa->descricao, $m)) {
                $lista = explode(',', $m[1]);
                $treinamentosSelecionados = array_map('trim', $lista);
            }
        }

        // VAI FAZER TREINAMENTO
        $vaiFazerTreinamento = (int)old(
            'vai_fazer_treinamento',
            $aso ? (int)$aso->vai_fazer_treinamento : 0
        );

        if ($vaiFazerTreinamento === 0 && !empty($treinamentosSelecionados)) {
            $vaiFazerTreinamento = 1;
        }

        return view('operacional.kanban.aso.create', [
            'cliente' => $cliente,
            'tarefa' => $tarefa,
            'tiposAso' => $tiposAso,
            'funcionarios' => $funcionarios,
            'funcoes' => $funcoes,
            'unidades' => $unidades,
            'dataAso' => $dataAso,
            'anexos' => $anexos,
            'unidadeSelecionada' => $unidadeSelecionada,
            'vaiFazerTreinamento' => $vaiFazerTreinamento,
            'treinamentosDisponiveis' => $treinamentosDisponiveis,
            'treinamentosSelecionados' => $treinamentosSelecionados,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Tarefa $tarefa)
    {
        $empresaId = auth()->user()->empresa_id;
        $cliente = $tarefa->cliente;

        $data = $request->validate([
            'funcionario_id' => ['nullable', 'exists:funcionarios,id'],
            'nome' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['nullable', 'date'],
            'rg' => ['nullable', 'string', 'max:255'],
            'funcao_id' => ['nullable', 'integer', 'exists:funcoes,id'],
            'tipo_aso' => ['required', 'in:admissional,periodico,demissional,mudanca_funcao,retorno_trabalho'],
            'data_aso' => ['required', 'date_format:Y-m-d'],
            'unidade_id' => ['required', 'exists:unidades_clinicas,id'],
            'vai_fazer_treinamento' => ['nullable', 'boolean'],
            'treinamentos' => ['array'],
            'treinamentos.*' => ['string'],
            'email_aso' => ['nullable', 'email'],
        ]);

        DB::transaction(function () use ($data, $empresaId, $cliente, $tarefa, $request) {

            // FUNCIONÁRIO (reaproveita/atualiza)
            if (!empty($data['funcionario_id'])) {
                $funcionario = Funcionario::where('empresa_id', $empresaId)
                    ->where('cliente_id', $cliente->id)
                    ->with('funcao')
                    ->findOrFail($data['funcionario_id']);
            } else {
                $funcionario = $tarefa->funcionario ?: new Funcionario([
                    'empresa_id' => $empresaId,
                    'cliente_id' => $cliente->id,
                ]);

                $funcionario->fill([
                    'nome' => $data['nome'],
                    'cpf' => $data['cpf'] ?? null,
                    'rg' => $data['rg'],
                    'data_nascimento' => $data['data_nascimento'],
                    'funcao_id' => $data['funcao_id'] ?? null,
                ]);
                $funcionario->save();
            }

            // monta descrição "bonita"
            $tipoAsoLabel = match ($data['tipo_aso']) {
                'admissional' => 'Admissional',
                'periodico' => 'Periódico',
                'demissional' => 'Demissional',
                'mudanca_funcao' => 'Mudança de Função',
                'retorno_trabalho' => 'Retorno ao Trabalho',
            };

            $descricao = "Tipo: {$tipoAsoLabel}";
            $treinamentos = $data['treinamentos'] ?? [];

            if (
                $data['tipo_aso'] === 'mudanca_funcao'
                && !empty($data['funcionario_id'])
                && !empty($data['funcao_id'])
            ) {
                $funcaoAnteriorNome = optional($funcionario->funcao)->nome ?? 'Não informada';
                $funcionario->funcao_id = $data['funcao_id'];
                $funcionario->save();

                $funcaoNovaNome = Funcao::find($data['funcao_id'])->nome ?? 'Não informada';
                $descricao .= " | Mudança de função: {$funcaoAnteriorNome} → {$funcaoNovaNome}";
            }

            if (!empty($data['vai_fazer_treinamento']) && !empty($treinamentos)) {
                $descricao .= ' | Treinamentos: ' . implode(', ', $treinamentos);
            }

            // atualiza tarefa
            $tarefa->update([
                'funcionario_id' => $funcionario->id,
                'descricao' => $descricao,
                'inicio_previsto' => $data['data_aso'],
            ]);

            // atualiza / cria aso_solicitacao
            $aso = $tarefa->asoSolicitacao ?: new AsoSolicitacoes([
                'empresa_id' => $empresaId,
                'cliente_id' => $cliente->id,
                'tarefa_id' => $tarefa->id,
            ]);

            $aso->fill([
                'funcionario_id' => $funcionario->id,
                'unidade_id' => $data['unidade_id'],
                'tipo_aso' => $data['tipo_aso'],
                'data_aso' => $data['data_aso'],
                'email_aso' => $data['email_aso'] ?? null,
                'vai_fazer_treinamento' => !empty($data['vai_fazer_treinamento']),
                'treinamentos' => $treinamentos,
            ]);

            Anexos::salvarDoRequest($request, 'anexos', [
                'empresa_id'     => $empresaId,
                'cliente_id'     => $cliente->id,
                'tarefa_id'      => $tarefa->id,
                'funcionario_id' => $funcionario->id ?? null,
                'uploaded_by'    => auth()->user()->id,
                'servico'        => 'ASO',
                'subpath'     => 'anexos-custom/' . $empresaId, // opcional, se quiser sobrescrever
            ]);

            $aso->save();
        });

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'Tarefa ASO atualizada com sucesso.');

    }



    public function asoCreate(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        // Funcionários já cadastrados para esse cliente
        $funcionarios = Funcionario::where('empresa_id', $empresaId)
            ->where('cliente_id', $cliente->id)
            ->orderBy('nome')
            ->get();

        // Unidades da clínica
        $unidades = \App\Models\UnidadeClinica::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        // Funções
        $funcoes = Funcao::where('empresa_id', $empresaId)
            ->orderBy('nome')
            ->get();

        // Tipos de ASO
        $tiposAso = [
            'admissional'      => 'Admissional',
            'periodico'        => 'Periódico',
            'demissional'      => 'Demissional',
            'mudanca_funcao'   => 'Mudança de Função',
            'retorno_trabalho' => 'Retorno ao Trabalho',
        ];

        // Treinamentos disponíveis
        $treinamentosDisponiveis = [
            'nr_35' => 'NR-35 - Trabalho em Altura',
            'nr_18' => 'NR-18 - Integração',
            'nr_12' => 'NR-12 - Máquinas e Equipamentos',
            'nr_06' => 'NR-06 - EPI',
            'nr_05' => 'NR-05 - CIPA Designada',
            'nr_01' => 'NR-01 - Ordem de Serviço',
            'nr_33' => 'NR-33 - Espaço Confinado',
            'nr_11' => 'NR-11 - Movimentação de Carga',
            'nr_10' => 'NR-10 - Elétrica'
        ];

        // Valores default para a view em modo "create"
        $treinamentosSelecionados = [];
        $vaiFazerTreinamento      = 0;
        $dataAso                  = null;
        $unidadeSelecionada       = null;

        return view('operacional.kanban.aso.create', [
            'cliente'                  => $cliente,
            'tarefa'                   => null, // importante pra view saber que é create
            'funcionarios'             => $funcionarios,
            'unidades'                 => $unidades,
            'tiposAso'                 => $tiposAso,
            'funcoes'                  => $funcoes,
            'treinamentosDisponiveis'  => $treinamentosDisponiveis,
            'treinamentosSelecionados' => $treinamentosSelecionados,
            'vaiFazerTreinamento'      => $vaiFazerTreinamento,
            'dataAso'                  => $dataAso,
            'unidadeSelecionada'       => $unidadeSelecionada,
            'anexos'                   => collect(),
        ]);
    }



}
