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

        $resolved = $this->resolveLocalPath($path);
        if ($resolved === null) {
            abort(404);
        }

        return response()->file($resolved['full_path']);
    }

    private function resolveLocalPath(string $path): ?array
    {
        $candidates = collect([
            $path,
            ltrim($path, '/'),
            preg_replace('#^public/#', '', ltrim($path, '/')),
            preg_replace('#^storage/#', '', ltrim($path, '/')),
        ])
            ->filter(fn ($candidate) => is_string($candidate) && $candidate !== '')
            ->unique()
            ->values();

        foreach (['public', 'local'] as $disk) {
            foreach ($candidates as $candidate) {
                if (Storage::disk($disk)->exists($candidate)) {
                    return [
                        'disk' => $disk,
                        'path' => $candidate,
                        'full_path' => Storage::disk($disk)->path($candidate),
                    ];
                }
            }
        }

        return null;
    }
}
