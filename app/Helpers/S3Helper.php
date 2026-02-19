<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class S3Helper
{
    private static function useLocalDisk(): bool
    {
        $appUrl = (string) config('app.url', '');

        return app()->environment('local')
            || str_contains($appUrl, 'localhost')
            || str_contains($appUrl, '127.0.0.1');
    }

    private static function diskName(): string
    {

        return self::useLocalDisk() ? 'public' : 's3';
    }

    public static function usingLocalDisk(): bool
    {
        return self::useLocalDisk();
    }

    /**
     * Faz upload de um arquivo para o S3 dentro da pasta "formed".
     * Retorna o caminho salvo (para guardar no banco).
     */
    public static function upload(UploadedFile $file, string $subpath = ''): string
    {
        $basePath = 'formed';

        if ($subpath) {
            $basePath .= '/' . trim($subpath, '/');
        }

        try {
            $disk = self::diskName();
            if (self::useLocalDisk()) {
                \Log::info('Upload em modo local (S3 ignorado).', [
                    'basePath' => $basePath,
                    'clientName' => $file->getClientOriginalName(),
                ]);
            }

            // você pode usar tanto store() quanto Storage::disk()->putFile()
            $path = $file->store($basePath, $disk);

            if ($path === false || $path === null) {
                \Log::error('S3 upload retornou false', [
                    'basePath' => $basePath,
                    'clientName' => $file->getClientOriginalName(),
                ]);
                throw new \RuntimeException('Falha ao fazer upload no S3 (retornou false).');
            }

            return $path;
        } catch (\Throwable $e) {
            \Log::error('Erro ao fazer upload no S3', [
                'basePath' => $basePath,
                'clientName' => $file->getClientOriginalName(),
                'message' => $e->getMessage(),
            ]);
            throw $e; // deixa estourar pra você ver o stack trace
        }
    }

    /**
     * Gera URL temporária (download seguro).
     */
    public static function temporaryUrl(string $path, int $minutes = 10): string
    {
        $disk = self::diskName();
        if ($disk === 'public') {
            return URL::temporarySignedRoute(
                'storage.proxy',
                now()->addMinutes($minutes),
                ['p' => base64_encode($path)]
            );
        }

        return Storage::disk($disk)->temporaryUrl(
            $path,
            now()->addMinutes($minutes)
        );
    }

    public static function url(string $path, int $minutes = 10): string
    {
        $disk = self::diskName();
        if ($disk === 'public') {
            return URL::temporarySignedRoute(
                'storage.proxy',
                now()->addMinutes($minutes),
                ['p' => base64_encode($path)]
            );
        }

        return Storage::disk($disk)->temporaryUrl(
            $path,
            now()->addMinutes($minutes)
        );
    }

    /**
     * Deleta um arquivo.
     */
    public static function delete(string $path): bool
    {
        return Storage::disk(self::diskName())->delete($path);
    }

    /**
     * Verifica se um arquivo existe.
     */
    public static function exists(string $path): bool
    {
        return Storage::disk(self::diskName())->exists($path);
    }
}
