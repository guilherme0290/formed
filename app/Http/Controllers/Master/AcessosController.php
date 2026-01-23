<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Papel;
use App\Models\Cliente;
use App\Models\ClienteContrato;
use App\Models\Comissao;
use App\Models\ContaReceberItem;
use App\Models\Proposta;
use App\Models\Tarefa;
use App\Models\Venda;

use Illuminate\Support\Facades\Password;

class AcessosController extends Controller
{
    public function index(Request $r)
    {
        $tab = $r->get('tab','papeis');

        $q       = $r->string('q')->toString();
        $papelId = $r->integer('papel_id');
        $status  = $r->string('status')->toString(); // 'ativos' | 'inativos' | ''
        $tipo    = $r->string('tipo')->toString();   // <- filtro de tipo

        $usuarios = User::with('papel')
            ->when($q, fn($b) => $b->where(fn($w) => $w
                ->where('name','like',"%$q%")
                ->orWhere('email','like',"%$q%")))
            ->when($papelId, fn($b) => $b->where('papel_id',$papelId))
            ->when($status==='ativos', fn($b) => $b->where('ativo',true))
            ->when($status==='inativos', fn($b) => $b->where('ativo',false))
            ->when($tipo, fn($b) => $b->where('tipo', $tipo)) // se tiver coluna tipo
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $papeis = Papel::with('permissoes')->orderBy('nome')->get();
        $permissoes = \App\Models\Permissao::orderBy('escopo')->orderBy('nome')->get()->groupBy('escopo');

        // pode ser fixo:
        $tipos = ['master','operacional','comercial.blade.php','financeiro','cliente'];


        return view('master.acessos.index', compact(
            'tab','papeis','usuarios','q','papelId','status','tipos','tipo','permissoes'
        ));
    }

    public function usuariosStore(Request $r)
    {
        $r->merge(['ativo' => $r->has('ativo')]);

        $data = $r->validate([
            'name'      => ['required','string','max:255'],
            'email'     => ['required','email','max:255','unique:users,email'],
            'password'   => ['required','string','min:6'],
            'telefone'  => ['nullable','string','max:30'],
            'papel_id'  => ['nullable','exists:papeis,id'],
            'ativo'     => ['nullable','boolean'],
        ]);

        $data['password'] = Hash::make($r->password);
        $data['empresa_id'] = 1;
        $data['ativo'] = $r->boolean('ativo');
        $data['telefone'] = !empty($data['telefone'])
            ? preg_replace('/\D+/', '', $data['telefone'])
            : null;

        User::create($data);

        return redirect()->route('master.acessos', ['tab' => 'usuarios'])
            ->with('ok', 'Usuário criado com sucesso');
    }

    public function usuariosUpdate(Request $r, User $user)
    {
        $r->merge(['ativo' => $r->has('ativo')]);

        $data = $r->validate([
            'name'      => ['required','string','max:255'],
            'email'     => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password'  => ['nullable','string','min:6'],
            'telefone'  => ['nullable','string','max:30'],
            'papel_id'  => ['nullable','exists:papeis,id'],
            'ativo'     => ['nullable','boolean'],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $data['ativo'] = $r->boolean('ativo');
        $data['telefone'] = !empty($data['telefone'])
            ? preg_replace('/\D+/', '', $data['telefone'])
            : null;
        $user->update($data);

        return redirect()->route('master.acessos', ['tab' => 'usuarios'])
            ->with('ok', 'Usuário atualizado.');
    }

    public function usuariosDestroy(User $user)
    {
        $reasons = [];

        if (auth()->id() === $user->id) {
            $reasons[] = 'não é possível excluir o próprio usuário';
        }

        $papelNome = mb_strtolower(optional($user->papel)->nome ?? '');

        if ($papelNome === 'cliente') {
            $clienteId = $user->cliente_id;
            if ($clienteId) {
                $hoje = now()->toDateString();
                $temContratoAtivo = ClienteContrato::query()
                    ->where('cliente_id', $clienteId)
                    ->where('status', 'ATIVO')
                    ->where(function ($q) use ($hoje) {
                        $q->whereNull('vigencia_inicio')
                            ->orWhereDate('vigencia_inicio', '<=', $hoje);
                    })
                    ->where(function ($q) use ($hoje) {
                        $q->whereNull('vigencia_fim')
                            ->orWhereDate('vigencia_fim', '>=', $hoje);
                    })
                    ->exists();

                if ($temContratoAtivo) {
                    $reasons[] = 'cliente possui contrato ativo';
                }

                if (Venda::where('cliente_id', $clienteId)->exists()) {
                    $reasons[] = 'cliente possui vendas vinculadas';
                }

                if (ContaReceberItem::where('cliente_id', $clienteId)->where('status', '!=', 'CANCELADO')->exists()) {
                    $reasons[] = 'cliente possui contas a receber vinculadas';
                }
            } else {
                $reasons[] = 'usuário cliente sem vínculo de cliente';
            }
        } else {
            if (Tarefa::where('responsavel_id', $user->id)->exists()) {
                $reasons[] = 'usuário possui tarefas vinculadas';
            }
            if (Proposta::where('vendedor_id', $user->id)->exists()) {
                $reasons[] = 'usuário possui propostas vinculadas';
            }
            if (Comissao::where('vendedor_id', $user->id)->exists()) {
                $reasons[] = 'usuário possui comissões vinculadas';
            }
            if (Cliente::where('vendedor_id', $user->id)->exists()) {
                $reasons[] = 'usuário possui clientes vinculados';
            }
            if (ClienteContrato::where('vendedor_id', $user->id)->exists()) {
                $reasons[] = 'usuário possui contratos vinculados';
            }
        }

        if (!empty($reasons)) {
            return back()->with(
                'erro',
                'Não é possível excluir este usuário. Considere desativar o usuário.'
            );
        }

        $user->delete();

        return redirect()->route('master.acessos', ['tab' => 'usuarios'])
            ->with('ok', 'Usuário removido.');
    }

    // Ativa/Desativa
    public function usuariosToggle(User $user)
    {
        $user->update(['ativo' => ! $user->ativo]);
        return back()->with('ok', $user->ativo ? 'Usuário ativado.' : 'Usuário desativado.');
    }

    public function usuariosReset(User $user)
    {
        // Envia link de redefiniÃ§Ã£o usando o Password Broker
        $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);

        // Quando dÃ¡ certo o broker retorna Password::RESET_LINK_SENT
        return back()->with(
            $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT ? 'ok' : 'err',
            $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
                ? 'Enviamos o link de redefinição para '.$user->email.'.'
                : 'Não foi possível enviar o link. Tente novamente.'
        );
    }

    public function usuariosSetPassword(Request $r, User $user)  // â tipos corretos
    {
        $data = $r->validate([
            'password' => ['required','string','min:6'],
        ]);

        $user->forceFill([
            'password' => bcrypt($data['password']),
        ])->save();

        return back()->with('ok', 'Senha atualizada para '.$user->email);
    }

}


