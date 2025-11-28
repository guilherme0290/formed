<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LtcatSolicitacoes extends Model
{
    protected $table = 'ltcat_solicitacoes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'tarefa_id',
        'responsavel_id',
        'tipo',
        'endereco_avaliacoes',
        'funcoes',
        'total_funcoes',
        'total_funcionarios',
        // Específico
        'nome_obra',
        'cnpj_contratante',
        'cei_cno' ,
        'endereco_obra',
    ];

    protected $casts = [
        'funcoes' => 'array',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tarefa()
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function getFuncoesResumoAttribute(): string
    {
        if (empty($this->funcoes) || !is_array($this->funcoes)) {
            return '';
        }

        $partes = [];

        foreach ($this->funcoes as $f) {
            $funcaoId = $f['funcao_id'] ?? null;
            $qtd      = $f['quantidade'] ?? null;

            if (!$funcaoId || !$qtd) {
                continue;
            }

            $fn = \App\Models\Funcao::find($funcaoId);
            if ($fn) {
                $partes[] = "{$fn->nome} ({$qtd})";
            }
        }

        return implode(', ', $partes);
    }
}
