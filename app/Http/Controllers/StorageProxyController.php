<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorageProxyController extends Controller
{
    public function show(Request $request)
    {
        $encoded = (string) $request->query('p', '');
        if ($encoded === '') {
            abort(404);
        }

        $path = base64_decode($encoded, true);
        if (!is_string($path) || $path === '') {
            abort(404);
        }

        // Evita path traversal no proxy local
        if (str_contains($path, '../') || str_contains($path, '..\\')) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($path));
    }
}
