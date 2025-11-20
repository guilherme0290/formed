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
        // pega o redirect da URL, ex: /login?redirect=operacional
        $redirect = $request->query('redirect', 'master'); // padrão master

        return view('auth.login', compact('redirect'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // se não vier nada, padrão = master
        $destino = $request->input('redirect', 'master');

        return match ($destino) {
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
