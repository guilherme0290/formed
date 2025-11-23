<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AprSolicitacoes extends Model
{
    protected $table = 'apr_solicitacoes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'tarefa_id',
        'responsavel_id',
        'endereco_atividade',
        'funcoes_envolvidas',
        'etapas_atividade',
    ];

    // Se quiser relações:
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
}
