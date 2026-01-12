<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Papel;

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
            ->with('ok', 'Usuario criado com sucesso');
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
        // Envia link de redefinição usando o Password Broker
        $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);

        // Quando dá certo o broker retorna Password::RESET_LINK_SENT
        return back()->with(
            $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT ? 'ok' : 'err',
            $status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT
                ? 'Enviamos o link de redefinição para '.$user->email.'.'
                : 'Não foi possível enviar o link. Tente novamente.'
        );
    }

    public function usuariosSetPassword(Request $r, User $user)  // ✅ tipos corretos
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


