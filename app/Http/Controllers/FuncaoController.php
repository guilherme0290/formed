<?php

namespace App\Http\Controllers;

use App\Models\Funcao;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FuncaoController extends Controller
{
    // Lista funções (para popular dropdown, por exemplo)
    public function index(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $query = Funcao::daEmpresa($empresaId)
            ->orderBy('nome');

        // se quiser só ativas por padrão
        if (! $request->boolean('with_inativas')) {
            $query->where('ativo', true);
        }

        // filtro por termo
        if ($busca = $request->get('q')) {
            $query->where('nome', 'like', "%{$busca}%");
        }

        $funcoes = $query->get();

        return response()->json($funcoes);
    }

    // Criar nova função (para ser usado no popup "Nova função")
    public function store(Request $request)
    {
        $empresaId = Auth::user()->empresa_id;

        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:255'],
            'cbo'       => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'ativo'     => ['nullable', 'boolean'],
        ]);

        $data['empresa_id'] = $empresaId;
        $data['ativo']      = $data['ativo'] ?? true;

        // garante unicidade por empresa
        if (
            Funcao::where('empresa_id', $empresaId)
                ->where('nome', $data['nome'])
                ->exists()
        ) {
            return response()->json([
                'ok'      => false,
                'message' => 'Já existe uma função com esse nome para esta empresa.',
            ], 422);
        }

        $funcao = Funcao::create($data);

        return response()->json([
            'ok'      => true,
            'message' => 'Função criada com sucesso.',
            'funcao'  => $funcao,
        ], 201);
    }

    public function storeAjax(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
        ]);

        $nome = trim($data['nome']);

        // Ajuste aqui a regra de unicidade conforme seu modelo (empresa_id, etc.)
        $query = Funcao::query()
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome, 'UTF-8')]);

        if (auth()->user()?->empresa_id) {
            $query->where('empresa_id', auth()->user()->empresa_id);
        }

        $jaExiste = $query->first();

        if ($jaExiste) {
            return response()->json([
                'ok'       => true,
                'existing' => true,
                'id'       => $jaExiste->id,
                'nome'     => $jaExiste->nome,
                'message'  => 'Função já cadastrada. Selecionada no dropdown.',
            ]);
        }

        $funcao = Funcao::create([
            'nome'       => $nome,
            'empresa_id' => auth()->user()->empresa_id ?? null, // ajuste se não tiver esse campo
        ]);

        return response()->json([
            'ok'   => true,
            'id'   => $funcao->id,
            'nome' => $funcao->nome,
        ]);
    }

    public function storefast(Request $request)
    {
        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id ?? null;

        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $funcao = Funcao::create([
                'empresa_id' => $empresaId,
                'nome'       => $data['nome'],
                'descricao'  => $data['descricao'] ?? null,
            ]);
        } catch (QueryException $e) {
            // 23000 = violação de integridade, 1062 = duplicate key no MySQL
            if ($e->getCode() === '23000' && ($e->errorInfo[1] ?? null) === 1062) {
                $message = 'Já existe uma função com esse nome para esta empresa.';

                // Resposta para o modal (AJAX / JSON)
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'ok'      => false,
                        'message' => $message,
                    ], 409);
                }

                // Fallback para submit normal
                return back()
                    ->withErrors(['nome' => $message])
                    ->withInput();
            }

            // Se for outro erro de banco, deixamos estourar pra você ver no log
            throw $e;
        }

        // Sucesso via AJAX / JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'ok'      => true,
                'message' => 'Função criada com sucesso.',
                'funcao'  => [
                    'id'   => $funcao->id,
                    'nome' => $funcao->nome,
                ],
            ], 201);
        }

        // Sucesso via submit normal
        return back()->with('ok', 'Função criada com sucesso.');
    }

    // Atualizar função
    public function update(Request $request, Funcao $funcao)
    {
        $empresaId = Auth::user()->empresa_id;

        if ($funcao->empresa_id !== $empresaId) {
            abort(403);
        }

        $data = $request->validate([
            'nome'      => ['required', 'string', 'max:255'],
            'cbo'       => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'ativo'     => ['nullable', 'boolean'],
        ]);

        // checar unique nome dentro da empresa
        $exists = Funcao::where('empresa_id', $empresaId)
            ->where('nome', $data['nome'])
            ->where('id', '<>', $funcao->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'ok'      => false,
                'message' => 'Já existe outra função com esse nome para esta empresa.',
            ], 422);
        }

        $funcao->update($data);

        return response()->json([
            'ok'      => true,
            'message' => 'Função atualizada com sucesso.',
            'funcao'  => $funcao,
        ]);
    }

    // "Excluir" função
    // Aqui estou removendo de verdade; se preferir só desativar, a gente troca.
    public function destroy(Funcao $funcao)
    {
        $empresaId = Auth::user()->empresa_id;

        if ($funcao->empresa_id !== $empresaId) {
            abort(403);
        }

        $temVinculo = $funcao->funcionarios()->exists()
            || $funcao->gheFuncoes()->exists();

        if ($temVinculo) {
            $funcao->update(['ativo' => false]);

            return response()->json([
                'ok'      => true,
                'message' => 'Função possui vínculos e foi inativada.',
                'funcao'  => $funcao,
            ]);
        }

        $funcao->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Função excluída com sucesso.',
            'funcao'  => $funcao,
        ]);
    }
}
