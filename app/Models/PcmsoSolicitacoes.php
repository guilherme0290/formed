<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcmsoSolicitacoes extends Model
{
    protected $table = 'pcmso_solicitacoes';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'tarefa_id',
        'responsavel_id',
        'tipo',
        'pgr_origem',
        'pgr_solicitacao_id',
        'pgr_arquivo_path',
        'obra_nome',
        'obra_cnpj_contratante',
        'obra_cei_cno',
        'obra_endereco',
        'prazo_dias',
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

    public function pgr()
    {
        return $this->belongsTo(PgrSolicitacoes::class, 'pgr_solicitacao_id');
    }
}
