<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarefa extends Model
{
    use HasFactory;

    protected $table = 'tarefas';

    protected $fillable = [
        'empresa_id',
        'coluna_id',
        'responsavel_id',
        'cliente_id',
        'funcionario_id',
        'servico_id',
        'titulo',
        'descricao',
        'prioridade',
        'status',
        'inicio_previsto',
        'fim_previsto',
        'finalizado_em',
        'data_prevista',
    ];

    protected $casts = [
        'inicio_previsto' => 'datetime',
        'fim_previsto'    => 'datetime',
        'finalizado_em'   => 'datetime',
        'data_prevista'   => 'date',
    ];

    // ===== RELACIONAMENTOS =====
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function responsavel()
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function coluna()
    {
        return $this->belongsTo(KanbanColuna::class, 'coluna_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TarefaLog::class);
    }

    public function ultimoLogMovimentacao(): ?TarefaLog
    {
        // helper opcional, se quiser usar no controller
        return $this->logs()->latest('created_at')->first();
    }


    public function pgr()
    {
        return $this->hasOne(PgrSolicitacoes::class);
    }

    public function pgrSolicitacao()
    {
        return $this->hasOne(PgrSolicitacoes::class, 'tarefa_id');
    }

    public function treinamentoNr()
    {
        return $this->hasMany(TreinamentoNR::class);
    }

    public function treinamentoNrDetalhes()
    {
        return $this->hasOne(TreinamentoNrDetalhes::class);
    }

    public function ltipSolicitacao()
    {
        return $this->hasOne(LtipSolicitacoes::class, 'tarefa_id');
    }

    public function aprSolicitacao()
    {
        return $this->hasOne(AprSolicitacoes::class, 'tarefa_id');
    }

    public function ltcatSolicitacao()
    {
        return $this->hasOne(LtcatSolicitacoes::class, 'tarefa_id');
    }

    public function pcmsoSolicitacao()
    {
        return $this->hasOne(PcmsoSolicitacoes::class, 'tarefa_id');
    }

    public function paeSolicitacao()
    {
        return $this->hasOne(PaeSolicitacoes::class, 'tarefa_id');
    }









}
