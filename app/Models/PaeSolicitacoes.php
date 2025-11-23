<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaeSolicitacoes extends Model
{
    protected $table = 'pae_solicitacoes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'tarefa_id',
        'responsavel_id',
        'endereco_local',
        'total_funcionarios',
        'descricao_instalacoes',
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
