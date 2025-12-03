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

        // redirect vindo do form (hidden)
        $destino = $request->input('redirect', 'master');
        $user = $request->user();
        $papelNome = strtoupper(optional($user->papel)->nome);

        // Se for Operacional, sempre vai pro painel operacional
        if ($papelNome === 'OPERACIONAL') {
            return redirect()->route('operacional.kanban');
        }

        // Redireciona conforme o destino pedido
        return match ($destino) {
            'cliente'     => redirect()->route('cliente.dashboard'),
            'operacional' => redirect()->route('operacional.kanban'),
            'master'      => redirect()->route('master.dashboard'),
            default       => redirect()->route('master.dashboard'),
        };
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
