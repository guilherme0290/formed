<?php

namespace App\Models;

use App\Helpers\S3Helper;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class Anexos extends Model
{
    protected $table = 'anexos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'tarefa_id',
        'uploaded_by',
        'servico',
        'nome_original',
        'path',
        'mime_type',
        'tamanho',
    ];

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function funcionario(): BelongsTo
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path
            ? S3Helper::temporaryUrl($this->path)   // já joga pro S3
            : null;
    }

    public function getTamanhoHumanoAttribute(): ?string
    {
        if (!$this->tamanho) return null;

        $bytes = (int) $this->tamanho;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.') . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Salva UM arquivo de upload no S3 + registra na tabela anexos.
     *
     * Parâmetros obrigatórios em $context:
     *  - empresa_id (int)
     *  - uploaded_by (int)  -> id do usuário
     *
     * Opcionais:
     *  - cliente_id, tarefa_id, funcionario_id, servico, subpath
     */
    public static function salvarUpload(UploadedFile $file, array $context): self
    {
        $empresaId = $context['empresa_id'] ?? null;
        $uploadedBy = $context['uploaded_by'] ?? null;

        if (!$empresaId || !$uploadedBy) {
            throw new InvalidArgumentException('empresa_id e uploaded_by são obrigatórios para salvar anexos.');
        }

        $servico = $context['servico'] ?? null;

        // subpasta padrão: anexos/{empresa_id}/{servico}
        $subpath = $context['subpath'] ?? ('anexos/' . $empresaId . ($servico ? '/' . strtolower($servico) : ''));

        // upload no S3 usando o helper que você já tem
        $path = S3Helper::upload($file, $subpath);

        // monta payload, deixando opcionais como null se não vierem
        $dados = [
            'empresa_id' => $empresaId,
            'cliente_id' => $context['cliente_id'] ?? null,
            'tarefa_id' => $context['tarefa_id'] ?? null,
            'funcionario_id' => $context['funcionario_id'] ?? null,
            'uploaded_by' => $uploadedBy,
            'servico' => $servico,

            'nome_original' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'tamanho' => $file->getSize(),
        ];

        return static::create($dados);
    }

    /**
     * Salva VÁRIOS arquivos (array ou Collection de UploadedFile).
     *
     * @param iterable<UploadedFile> $files
     * @return \Illuminate\Support\Collection<Anexos>
     */
    public static function salvarVarios(iterable $files, array $context): Collection
    {
        $criados = collect();

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $criados->push(static::salvarUpload($file, $context));
        }

        return $criados;
    }

    /**
     * Atalho: lê direto do Request um campo de arquivos (ex: "anexos").
     *
     * Se não tiver arquivo, retorna Collection vazia.
     */
    public static function salvarDoRequest(Request $request, string $field, array $context): Collection
    {
        if (!$request->hasFile($field)) {
            return collect();
        }

        $files = $request->file($field);

        // quando é um único arquivo, o Laravel entrega UploadedFile, não array
        if ($files instanceof UploadedFile) {
            return collect([static::salvarUpload($files, $context)]);
        }

        return static::salvarVarios($files, $context);
    }
}
