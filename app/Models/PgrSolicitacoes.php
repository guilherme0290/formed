<?php

namespace App\Models;

use App\Helpers\S3Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PgrSolicitacoes extends Model
{
    protected $table = 'pgr_solicitacoes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'tarefa_id',
        'responsavel_id',
        'tipo',
        'com_art',
        'valor_art',
        'qtd_homens',
        'qtd_mulheres',
        'total_trabalhadores',
        'funcoes',
        'com_pcms0',
        'contratante_nome',
        'contratante_cnpj',
        'obra_nome',
        'obra_endereco',
        'obra_cej_cno',
        'obra_turno_trabalho',
    ];

    protected $casts = [
        'com_art'            => 'bool',
        'com_pcms0'          => 'bool',
        'funcoes'            => 'array',   // JSON -> array
        'valor_art'          => 'decimal:2',
        'qtd_homens'         => 'integer',
        'qtd_mulheres'       => 'integer',
        'total_trabalhadores'=> 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function getFuncoesResumoAttribute()
    {
        if (!$this->funcoes) return '';

        $lista = [];

        foreach ($this->funcoes as $item) {
            $funcao = \App\Models\Funcao::find($item['funcao_id']);
            if ($funcao) {
                $lista[] = "{$funcao->nome} ({$item['quantidade']})";
            }
        }

        return implode(', ', $lista);
    }

    public function getPgrArquivoUrlAttribute(): ?string
    {
        if (!$this->pgr_arquivo_path) {
            return null;
        }

        return S3Helper::temporaryUrl($this->pgr_arquivo_path, 10);
    }
}
