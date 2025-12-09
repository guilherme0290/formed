<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs que NÃO vão exigir CSRF.
     * Em DESENVOLVIMENTO podemos liberar o /login sem problema.
     */
    protected $except = [
        'login',
    ];
}
