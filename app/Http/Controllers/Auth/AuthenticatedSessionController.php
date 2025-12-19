<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        $redirect = $request->query('redirect', 'master');
        return view('auth.login', compact('redirect'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $destino   = $request->input('redirect', 'master');
        $user      = $request->user();
        $papelNome = mb_strtolower(optional($user->papel)->nome ?? '');

        // MASTER: pode ir a qualquer módulo exceto cliente
        if ($papelNome === 'master') {
            if ($destino === 'operacional') {
                return redirect()->route('operacional.kanban');
            }
            if ($destino === 'comercial') {
                return redirect()->route('comercial.dashboard');
            }
            return redirect()->route('master.dashboard');
        }

        // OPERACIONAL: sempre operacional
        if ($papelNome === 'operacional') {
            return redirect()->route('operacional.kanban');
        }

        // COMERCIAL: sempre comercial
        if ($papelNome === 'comercial') {
            return redirect()->route('comercial.dashboard');
        }

        // CLIENTE: somente portal do cliente
        if ($papelNome === 'cliente') {
            if ($user->cliente_id) {
                $request->session()->put('portal_cliente_id', $user->cliente_id);
                return redirect()->route('cliente.dashboard');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', ['redirect' => 'cliente'])
                ->with('error', 'Seu usuário não está vinculado a nenhum cliente. Acesse com um usuário de cliente.');
        }

        // Perfil não reconhecido: desloga e exibe página informativa
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()
            ->view('errors.perfil-nao-suportado', ['papel' => $papelNome ?: 'desconhecido'], 403);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
