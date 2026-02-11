<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsoSolicitacoes extends Model
{
    use HasFactory;

    protected $table = 'aso_solicitacoes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'tarefa_id',
        'funcionario_id',
        'unidade_id',
        'tipo_aso',
        'data_aso',
        'email_aso',
        'vai_fazer_treinamento',
        'treinamentos',
        'treinamento_pacote',
    ];

    protected $casts = [
        'data_aso'              => 'date',
        'vai_fazer_treinamento' => 'boolean',
        'treinamentos'          => 'array',
        'treinamento_pacote'    => 'array',
    ];

    public function tarefa()
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function unidade()
    {
        return $this->belongsTo(UnidadeClinica::class, 'unidade_id');
    }
}
