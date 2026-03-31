<?php

namespace App\Http\Controllers;

use App\Helpers\S3Helper;
use App\Models\Anexos;
use App\Models\Tarefa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnexoController extends Controller
{
    public function store(Tarefa $tarefa, Request $request)
    {
        $usuario = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $request->validate([
            'arquivos' => ['required', 'array', 'min:1'],
            'arquivos.*' => ['file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB
        ]);

        foreach ($request->file('arquivos') as $file) {
            // subpasta no S3 (ex: formed/anexos/123/arquivo.pdf)
            $subpath = 'anexos/'.$tarefa->id;

            $path = S3Helper::upload($file, $subpath);

            Anexos::create([
                'empresa_id' => $empresaId,
                'cliente_id' => $tarefa->cliente_id,
                'tarefa_id' => $tarefa->id,
                'uploaded_by' => $usuario->id,
                'servico' => optional($tarefa->servico)->nome, // 'ASO', 'PGR', 'PCMSO'...
                'nome_original' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'tamanho' => $file->getSize(),
            ]);
        }

        return back()->with('ok', 'Anexos enviados com sucesso.');
    }

    public function view(Anexos $anexo, Request $request)
    {
        $this->autorizarEmpresa($anexo);

        return $this->responderArquivo($anexo, false);
    }

    public function destroy(Anexos $anexo, Request $request)
    {
        $this->autorizarEmpresa($anexo);

        // apaga do S3 se existir
        if ($anexo->path && S3Helper::exists($anexo->path)) {
            S3Helper::delete($anexo->path);
        }

        $anexo->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Anexo removido com sucesso.',
            ]);
        }

        return back()->with('ok', 'Anexo removido com sucesso.');
    }

    public function download(Anexos $anexo)
    {
        $usuario = Auth::user();
        $empresaId = $usuario->empresa_id;

        abort_if($anexo->empresa_id !== $empresaId, 403);

        return $this->responderArquivo($anexo, true);
    }

    public function downloadPublico(string $token)
    {
        $anexo = Anexos::query()
            ->where('public_token', $token)
            ->firstOrFail();

        return $this->responderArquivo($anexo, false);
    }

    protected function autorizarEmpresa(Anexos $anexo): void
    {
        $user = auth()->user();

        if (! $user || $anexo->empresa_id !== $user->empresa_id) {
            abort(403);
        }

        if ($user->isCliente() && $anexo->foiEnviadoPorCliente()) {
            abort(403);
        }
    }

    private function responderArquivo(Anexos $anexo, bool $forcarDownload): StreamedResponse
    {
        abort_if(blank($anexo->path), 404);

        $disk = $this->resolverDiskParaPath((string) $anexo->path);
        abort_if($disk === null, 404);

        $stream = Storage::disk($disk)->readStream($anexo->path);
        abort_if($stream === false, 404);

        $nome = trim((string) ($anexo->nome_original ?: basename((string) $anexo->path)));
        $nome = $nome !== '' ? $nome : 'anexo';
        $mime = trim((string) ($anexo->mime_type ?: 'application/octet-stream'));
        $disposition = $forcarDownload || ! str_starts_with($mime, 'image/') && $mime !== 'application/pdf'
            ? 'attachment'
            : 'inline';

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.addslashes($nome).'"',
        ]);
    }

    private function resolverDiskParaPath(string $path): ?string
    {
        foreach (['public', 's3'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($path)) {
                    return $disk;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }
}
