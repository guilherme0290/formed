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
        'contratante_razao_social',
        'contratante_cnpj',
        'contratante_responsavel_nome',
        'contratante_telefone',
        'contratante_email',
        'obra_nome',
        'obra_endereco',
        'obra_cidade',
        'obra_uf',
        'obra_cep',
        'obra_area_setor',
        'atividade_descricao',
        'atividade_data_inicio',
        'atividade_data_termino_prevista',
        'etapas_json',
        'equipe_json',
        'epis_json',
        'status',
        'aprovada_em',
        'aprovada_por',
        'endereco_atividade',
        'funcoes_envolvidas',
        'etapas_atividade',
    ];

    protected $casts = [
        'atividade_data_inicio' => 'date',
        'atividade_data_termino_prevista' => 'date',
        'etapas_json' => 'array',
        'equipe_json' => 'array',
        'epis_json' => 'array',
        'aprovada_em' => 'datetime',
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

    public function aprovadoPor()
    {
        return $this->belongsTo(User::class, 'aprovada_por');
    }
}
