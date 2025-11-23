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
}
