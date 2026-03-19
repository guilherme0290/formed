<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExameToxicologicoSolicitacao extends Model
{
    protected $table = 'exame_toxicologico_solicitacoes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'tarefa_id',
        'funcionario_id',
        'responsavel_id',
        'unidade_id',
        'tipo_exame',
        'nome_completo',
        'cpf',
        'rg',
        'data_nascimento',
        'telefone',
        'email_envio',
        'data_realizacao',
    ];

    protected $casts = [
        'data_nascimento' => 'date',
        'data_realizacao' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tarefa()
    {
        return $this->belongsTo(Tarefa::class, 'tarefa_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function unidade()
    {
        return $this->belongsTo(UnidadeClinica::class, 'unidade_id');
    }
}
