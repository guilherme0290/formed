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
        $papelNome = strtoupper(optional($user->papel)->nome);

        // Se for OPERACIONAL, sempre manda pro operacional
        if ($papelNome === 'OPERACIONAL') {
            return redirect()->route('operacional.kanban');
        }

        // =========================
        // MÓDULO CLIENTE
        // =========================
        if ($destino === 'cliente') {

            // usuário precisa estar vinculado a um cliente
            if ($user->cliente_id) {

                // salva o cliente escolhido na sessão do portal
                $request->session()->put('portal_cliente_id', $user->cliente_id);

                return redirect()->route('cliente.dashboard');
            }


        }

        // =========================
        // MÓDULO OPERACIONAL
        // =========================
        if ($destino === 'operacional') {
            return redirect()->route('operacional.kanban');
        }

        // =========================
        // MASTER (padrão)
        // =========================
        return redirect()->route('master.dashboard');
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
