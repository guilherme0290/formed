<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ForcePasswordController extends Controller
{
    public function show()
    {
        return view('auth.force-password');
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'password' => ['required','confirmed','min:8'],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ])->save();

        $papelNome = mb_strtolower(optional($user->papel)->nome ?? '');
        if ($papelNome === 'comercial') {
            return redirect()->route('comercial.dashboard')->with('ok', 'Senha atualizada com sucesso.');
        }
        if ($papelNome === 'operacional') {
            return redirect()->route('operacional.kanban')->with('ok', 'Senha atualizada com sucesso.');
        }
        if ($papelNome === 'cliente') {
            return redirect()->route('cliente.dashboard')->with('ok', 'Senha atualizada com sucesso.');
        }

        return redirect()->route('master.dashboard')->with('ok', 'Senha atualizada com sucesso.');
    }
}
