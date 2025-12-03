<?php

namespace App\Http\Controllers;

use App\Helpers\S3Helper;
use App\Models\Anexos;
use App\Models\Tarefa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnexoController extends Controller
{
    public function store(Tarefa $tarefa, Request $request)
    {
        $usuario   = $request->user();
        $empresaId = $usuario->empresa_id;

        abort_if($tarefa->empresa_id !== $empresaId, 403);

        $request->validate([
            'arquivos'   => ['required', 'array', 'min:1'],
            'arquivos.*' => ['file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB
        ]);

        foreach ($request->file('arquivos') as $file) {
            // subpasta no S3 (ex: formed/anexos/123/arquivo.pdf)
            $subpath = 'anexos/' . $tarefa->id;

            $path = S3Helper::upload($file, $subpath);

            Anexos::create([
                'empresa_id'    => $empresaId,
                'cliente_id'    => $tarefa->cliente_id,
                'tarefa_id'     => $tarefa->id,
                'uploaded_by'   => $usuario->id,
                'servico'       => optional($tarefa->servico)->nome, // 'ASO', 'PGR', 'PCMSO'...
                'nome_original' => $file->getClientOriginalName(),
                'path'          => $path,
                'mime_type'     => $file->getClientMimeType(),
                'tamanho'       => $file->getSize(),
            ]);
        }

        return back()->with('ok', 'Anexos enviados com sucesso.');
    }
    public function view(Anexos $anexo, Request $request)
    {
        $this->autorizarEmpresa($anexo);

        // URL temporária para abrir no navegador
        $url = S3Helper::temporaryUrl($anexo->path, 10);

        return redirect()->away($url);
    }

    public function destroy(Anexos $anexo, Request $request)
    {
        $this->autorizarEmpresa($anexo);

        // apaga do S3 se existir
        if ($anexo->path && S3Helper::exists($anexo->path)) {
            S3Helper::delete($anexo->path);
        }

        $anexo->delete();

        return back()->with('ok', 'Anexo removido com sucesso.');
    }


    public function download(Anexos $anexo)
    {
        $usuario   = Auth::user();
        $empresaId = $usuario->empresa_id;

        abort_if($anexo->empresa_id !== $empresaId, 403);

        $url = \App\Helpers\S3Helper::temporaryUrl($anexo->path, 5);

        return redirect($url);
    }



    protected function autorizarEmpresa(Anexos $anexo): void
    {
        $user = auth()->user();

        if (!$user || $anexo->empresa_id !== $user->empresa_id) {
            abort(403);
        }
    }
}
