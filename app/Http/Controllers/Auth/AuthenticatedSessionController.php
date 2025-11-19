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
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $destino = $request->input('redirect');

        return match ($destino) {
            'operacional' => redirect()->route('operacional.kanban'),
            'master'      => redirect()->route('master.dashboard'),


            // por enquanto, apontando pro master até os outros módulos existirem:
            //'comercial'   => redirect()->route('master.dashboard'),
            //'cliente'     => redirect()->route('master.dashboard'),
            //'financeiro'  => redirect()->route('master.dashboard'),

           // default       => redirect()->route('master.dashboard'),
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
