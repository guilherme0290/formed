<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Funcionario;
use App\Models\Tarefa;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class FuncionarioArquivosZipService
{
    public function gerarZip(Cliente $cliente, Funcionario $funcionario, bool $includeAnexos = true): string
    {
        $query = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->where('funcionario_id', $funcionario->id)
            ->orderByDesc('finalizado_em')
            ->orderByDesc('updated_at');

        $query->with($includeAnexos ? ['servico', 'anexos'] : ['servico']);
        $tarefas = $query->get();

        return $this->gerarZipComTarefas($tarefas, 'funcionario-arquivos-' . $funcionario->id, $includeAnexos);
    }

    public function gerarZipPorIds(Cliente $cliente, array $tarefaIds, ?Funcionario $funcionario = null, bool $includeAnexos = true): string
    {
        $tarefas = $this->buscarTarefasPorIds($cliente, $tarefaIds, $funcionario, $includeAnexos);

        return $this->gerarZipComTarefas($tarefas, 'arquivos-selecionados', $includeAnexos);
    }

    public function listarArquivosPorIds(Cliente $cliente, array $tarefaIds, ?Funcionario $funcionario = null, bool $includeAnexos = true): array
    {
        $tarefas = $this->buscarTarefasPorIds($cliente, $tarefaIds, $funcionario, $includeAnexos);

        $arquivos = $this->deduplicarPorPath($this->coletarArquivosDasTarefas($tarefas, $includeAnexos));
        if (empty($arquivos)) {
            throw new RuntimeException('Nenhum arquivo encontrado para os filtros informados.');
        }

        $disponiveis = [];
        foreach ($arquivos as $arquivo) {
            $disk = $this->resolverDiskParaPath((string) ($arquivo['path'] ?? ''));
            if (!$disk) {
                continue;
            }

            $disponiveis[] = [
                'disk' => $disk,
                'path' => (string) $arquivo['path'],
                'name' => (string) ($arquivo['name'] ?? 'arquivo'),
            ];
        }

        if (empty($disponiveis)) {
            throw new RuntimeException('Nenhum arquivo disponivel para download nestes itens.');
        }

        return $disponiveis;
    }

    private function gerarZipComTarefas($tarefas, string $zipPrefix, bool $includeAnexos): string
    {
        $arquivos = $this->deduplicarPorPath($this->coletarArquivosDasTarefas($tarefas, $includeAnexos));
        if (empty($arquivos)) {
            throw new RuntimeException('Nenhum arquivo encontrado para os filtros informados.');
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
            throw new RuntimeException('Nao foi possivel criar diretorio temporario para gerar o ZIP.');
        }

        $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipPrefix . '-' . time() . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Nao foi possivel criar o arquivo ZIP.');
        }

        $nomesNoZip = [];
        $totalAdicionados = 0;
        foreach ($arquivos as $arquivo) {
            $disk = $this->resolverDiskParaPath($arquivo['path']);
            if (!$disk) {
                continue;
            }

            $stream = Storage::disk($disk)->readStream($arquivo['path']);
            if ($stream === false) {
                continue;
            }

            $conteudo = stream_get_contents($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            if ($conteudo === false) {
                continue;
            }

            $nomeZip = $this->garantirNomeUnico($arquivo['name'], $nomesNoZip);
            $zip->addFromString($nomeZip, $conteudo);
            $totalAdicionados++;
        }

        $zip->close();

        if ($totalAdicionados === 0) {
            @unlink($zipPath);
            throw new RuntimeException('Nenhum arquivo disponivel para download nestes itens.');
        }

        return $zipPath;
    }

    private function buscarTarefasPorIds(Cliente $cliente, array $tarefaIds, ?Funcionario $funcionario, bool $includeAnexos)
    {
        $ids = collect($tarefaIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw new RuntimeException('Selecione ao menos um arquivo para baixar.');
        }

        $query = Tarefa::query()
            ->where('cliente_id', $cliente->id)
            ->whereIn('id', $ids->all())
            ->orderByDesc('finalizado_em')
            ->orderByDesc('updated_at');

        $query->with($includeAnexos ? ['servico', 'anexos'] : ['servico']);

        if ($funcionario) {
            $query->where('funcionario_id', $funcionario->id);
        }

        $tarefas = $query->get();
        if ($tarefas->isEmpty()) {
            throw new RuntimeException('Nenhum arquivo valido encontrado para os itens selecionados.');
        }

        return $tarefas;
    }

    private function coletarArquivosDasTarefas($tarefas, bool $includeAnexos): array
    {
        $arquivos = [];

        foreach ($tarefas as $tarefa) {
            if (!empty($tarefa->path_documento_cliente)) {
                $arquivos[] = [
                    'path' => $tarefa->path_documento_cliente,
                    'name' => $this->nomeArquivoDocumentoCliente($tarefa),
                ];
            }

            if ($includeAnexos) {
                foreach ($tarefa->anexos as $anexo) {
                    if (empty($anexo->path)) {
                        continue;
                    }

                    $arquivos[] = [
                        'path' => $anexo->path,
                        'name' => $this->nomeArquivoAnexo($tarefa->id, (string) ($anexo->nome_original ?? 'anexo')),
                    ];
                }
            }
        }

        return $arquivos;
    }

    private function deduplicarPorPath(array $arquivos): array
    {
        $vistos = [];
        $resultado = [];
        foreach ($arquivos as $arquivo) {
            $path = (string) ($arquivo['path'] ?? '');
            if ($path === '' || isset($vistos[$path])) {
                continue;
            }
            $vistos[$path] = true;
            $resultado[] = $arquivo;
        }

        return $resultado;
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

    private function nomeArquivoDocumentoCliente(Tarefa $tarefa): string
    {
        $servico = trim((string) optional($tarefa->servico)->nome);
        $servico = $servico !== '' ? $servico : 'documento';

        $base = sprintf('tarefa-%d-%s-documento-cliente', (int) $tarefa->id, Str::slug($servico));
        $ext = pathinfo((string) $tarefa->path_documento_cliente, PATHINFO_EXTENSION);
        $ext = $ext !== '' ? $ext : 'pdf';

        return $base . '.' . strtolower($ext);
    }

    private function nomeArquivoAnexo(int $tarefaId, string $nomeOriginal): string
    {
        $ext = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
        $base = pathinfo($nomeOriginal, PATHINFO_FILENAME);
        $base = trim($base) !== '' ? $base : 'anexo';

        $nome = sprintf('tarefa-%d-anexo-%s', $tarefaId, Str::slug($base));
        if ($ext !== '') {
            $nome .= '.' . strtolower($ext);
        }

        return $nome;
    }

    private function garantirNomeUnico(string $nome, array &$usados): string
    {
        $info = pathinfo($nome);
        $base = $info['filename'] ?? 'arquivo';
        $ext = isset($info['extension']) ? '.' . $info['extension'] : '';

        $final = $base . $ext;
        $i = 2;
        while (isset($usados[$final])) {
            $final = $base . '-' . $i . $ext;
            $i++;
        }

        $usados[$final] = true;
        return $final;
    }
}
