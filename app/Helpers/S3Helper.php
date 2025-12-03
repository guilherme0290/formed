<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class S3Helper
{
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
            // você pode usar tanto store() quanto Storage::disk()->putFile()
            $path = $file->store($basePath, 's3');

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
        return Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes($minutes)
        );
    }

    public static function url(string $path, int $minutes = 10): string
    {
        return Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes($minutes)
        );
    }

    /**
     * Deleta um arquivo.
     */
    public static function delete(string $path): bool
    {
        return Storage::disk('s3')->delete($path);
    }

    /**
     * Verifica se um arquivo existe.
     */
    public static function exists(string $path): bool
    {
        return Storage::disk('s3')->exists($path);
    }
}
