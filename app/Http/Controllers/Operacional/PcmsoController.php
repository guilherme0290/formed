<?php

namespace App\Http\Controllers\Operacional;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\KanbanColuna;
use App\Models\PcmsoSolicitacoes;
use App\Models\Servico;
use App\Models\Tarefa;
use App\Models\TarefaLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PcmsoController extends Controller
{
    /**
     * Tela: PCMSO - Selecione o Tipo (Matriz / Específico)
     */
    public function selecionarTipo(Cliente $cliente, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $origem = $request->query('origem'); // 'cliente' ou null

        return view('operacional.kanban.pcmso.tipo', [
            'cliente' => $cliente,
            'origem'  => $origem,
        ]);
    }

    /**
     * Tela: "Você já possui o PGR?" para Matriz/Específico
     */
    public function perguntaPgr(Cliente $cliente, string $tipo, Request $request)
    {
        $usuario = $request->user();
        abort_if($cliente->empresa_id !== $usuario->empresa_id, 403);

        $tipo = $tipo === 'especifico' ? 'especifico' : 'matriz';

        return view('operacional.kanban.pcmso.possui-pgr', [
            'cliente' => $cliente,
            'tipo'    => $tipo,
        ]);
    }

    /**
     * Formulário para anexar PGR + (campos da obra se for específico)
     */
    public function createComPgr(Cliente $cliente, string $tipo, Request $request)
    {
        $usuario = $request->user();
        abort_if($cliente->empresa_id !== $usuario->empresa_id, 403);

        $tipo = $tipo === 'especifico' ? 'especifico' : 'matriz';

        if ($tipo === 'matriz') {
            return view('operacional.kanban.pcmso.form_matriz', [
                'cliente' => $cliente,
                'tipo'    => $tipo,
            ]);
        }

        return view('operacional.kanban.pcmso.form_especifico', [
            'cliente' => $cliente,
            'tipo'    => $tipo,
        ]);
    }

    /**
     * Salvar PCMSO (Matriz ou Específico) com PGR anexado
     */
    public function storeComPgr(Cliente $cliente, string $tipo, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($cliente->empresa_id !== $empresaId, 403);

        $tipo = $tipo === 'especifico' ? 'especifico' : 'matriz';

        // regras comuns
        $rules = [
            'pgr_arquivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];

        // se for específico, exige dados da obra
        if ($tipo === 'especifico') {
            $rules = array_merge($rules, [
                'obra_nome'              => ['required', 'string', 'max:255'],
                'obra_cnpj_contratante'  => [ 'string', 'max:20'],
                'obra_cei_cno'           => [ 'string', 'max:50'],
                'obra_endereco'          => [ 'string', 'max:255'],
            ]);
        }

        $data = $request->validate($rules);

        // armazena PDF do PGR
        $path = $request->file('pgr_arquivo')->store('pcmso_pgr', 'public');

        // coluna inicial (Pendente)
        $colunaInicial = KanbanColuna::where('empresa_id', $empresaId)
            ->where('slug', 'pendente')
            ->first()
            ?? KanbanColuna::where('empresa_id', $empresaId)->orderBy('ordem')->first();

        // serviço PCMSO (ajuste o nome se estiver diferente na tabela servicos)
        $servicoPcmso = Servico::where('empresa_id', $empresaId)
            ->where('nome', 'PCMSO')
            ->first();

        $tipoLabel = $tipo === 'matriz' ? 'Matriz' : 'Específico';
        $prazoDias = 10;

        $tarefaId = null;

        DB::transaction(function () use (
            $empresaId,
            $cliente,
            $usuario,
            $colunaInicial,
            $servicoPcmso,
            $tipo,
            $tipoLabel,
            $prazoDias,
            $path,
            $data,
            &$tarefaId
        ) {
            // cria Tarefa no Kanban
            $tarefa = Tarefa::create([
                'empresa_id'      => $empresaId,
                'cliente_id'      => $cliente->id,
                'responsavel_id'  => $usuario->id,
                'coluna_id'       => optional($colunaInicial)->id,
                'servico_id'      => optional($servicoPcmso)->id,
                'titulo'          => "PCMSO - {$tipoLabel}",
                'descricao'       => "PCMSO - {$tipoLabel} com PGR anexado pelo cliente.",
                'inicio_previsto' => now(),
            ]);

            $tarefaId = $tarefa->id;

            // cria registro PCMSO
            PcmsoSolicitacoes::create([
                'empresa_id'            => $empresaId,
                'cliente_id'            => $cliente->id,
                'tarefa_id'             => $tarefa->id,
                'responsavel_id'        => $usuario->id,
                'tipo'                  => $tipo,
                'pgr_origem'            => 'arquivo_cliente',
                'pgr_arquivo_path'      => $path,
                'obra_nome'             => $data['obra_nome']             ?? null,
                'obra_cnpj_contratante' => $data['obra_cnpj_contratante'] ?? null,
                'obra_cei_cno'          => $data['obra_cei_cno']          ?? null,
                'obra_endereco'         => $data['obra_endereco']         ?? null,
                'prazo_dias'            => $prazoDias,
            ]);

            // log inicial da tarefa
            TarefaLog::create([
                'tarefa_id'     => $tarefa->id,
                'user_id'       => $usuario->id,
                'de_coluna_id'  => null,
                'para_coluna_id'=> optional($colunaInicial)->id,
                'acao'          => 'criado',
                'observacao'    => 'Tarefa PCMSO criada pelo usuário.',
            ]);
        });

        if ($usuario->isCliente()) {
            return redirect()
                ->route('cliente.dashboard')
                ->with('ok', "Solicitação de PCMSO {$tipoLabel} criada com sucesso e enviada para análise.");
        }

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', "Tarefa PCMSO {$tipoLabel} criada com sucesso!");
    }

    public function edit(Tarefa $tarefa, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $pcmso = $tarefa->pcmsoSolicitacao;
        abort_if(!$pcmso, 404);

        $cliente = $tarefa->cliente;
        $tipo    = $pcmso->tipo === 'especifico' ? 'especifico' : 'matriz';

        if ($tipo === 'matriz') {
            return view('operacional.kanban.pcmso.form_matriz', [
                'cliente' => $cliente,
                'tipo'    => $tipo,
                'pcmso'   => $pcmso,
                'isEdit'  => true,
            ]);
        }

        return view('operacional.kanban.pcmso.form_especifico', [
            'cliente' => $cliente,
            'tipo'    => $tipo,
            'pcmso'   => $pcmso,
            'isEdit'  => true,
        ]);
    }

    public function update(Tarefa $tarefa, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $pcmso = $tarefa->pcmsoSolicitacao;
        abort_if(!$pcmso, 404);

        $cliente = $tarefa->cliente;
        $tipo    = $pcmso->tipo === 'especifico' ? 'especifico' : 'matriz';

        // regras comuns
        $rules = [
            'pgr_arquivo'    => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'remover_arquivo'=> ['nullable', 'boolean'],
        ];

        // se for específico, atualiza dados da obra também
        if ($tipo === 'especifico') {
            $rules = array_merge($rules, [
                'obra_nome'              => ['required', 'string', 'max:255'],
                'obra_cnpj_contratante'  => ['nullable', 'string', 'max:20'],
                'obra_cei_cno'           => ['nullable', 'string', 'max:50'],
                'obra_endereco'          => ['nullable', 'string', 'max:255'],
            ]);
        }

        $data = $request->validate($rules);

        $pathAtual = $pcmso->pgr_arquivo_path;

        // se marcou para remover o arquivo
        $remover = (bool)($data['remover_arquivo'] ?? false);
        if ($remover && $pathAtual) {
            if (Storage::disk('public')->exists($pathAtual)) {
                Storage::disk('public')->delete($pathAtual);
            }
            $pathAtual = null;
        }

        // se subiu novo arquivo, substitui
        if ($request->hasFile('pgr_arquivo')) {
            if ($pathAtual && Storage::disk('public')->exists($pathAtual)) {
                Storage::disk('public')->delete($pathAtual);
            }
            $pathAtual = $request->file('pgr_arquivo')->store('pcmso_pgr', 'public');
        }

        // regra opcional: não deixar ficar sem PGR anexado
        if (!$pathAtual) {
            return back()
                ->withInput()
                ->withErrors(['pgr_arquivo' => 'É necessário manter um PGR anexado (envie um novo arquivo ou não marque para remover).']);
        }

        // monta payload de atualização
        $updateData = [
            'pgr_arquivo_path' => $pathAtual,
        ];

        if ($tipo === 'especifico') {
            $updateData = array_merge($updateData, [
                'obra_nome'             => $data['obra_nome'],
                'obra_cnpj_contratante' => $data['obra_cnpj_contratante'] ?? null,
                'obra_cei_cno'          => $data['obra_cei_cno'] ?? null,
                'obra_endereco'         => $data['obra_endereco'] ?? null,
            ]);
        }

        $pcmso->update($updateData);

        return redirect()
            ->route('operacional.kanban')
            ->with('ok', 'PCMSO atualizado com sucesso!');
    }
}
