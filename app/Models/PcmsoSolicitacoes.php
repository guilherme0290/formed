<?php

namespace App\Models;

use App\Helpers\S3Helper;
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
        'funcoes',
        'obra_nome',
        'obra_cnpj_contratante',
        'obra_cei_cno',
        'obra_endereco',
        'prazo_dias',
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

    public function pgr()
    {
        return $this->belongsTo(PgrSolicitacoes::class, 'pgr_solicitacao_id');
    }

    public function setCnpjAttribute($value)
    {
        $this->attributes['obra_cnpj_contratante'] = $value
            ? preg_replace('/\D+/', '', $value)
            : null;
    }

    public function getPgrArquivoUrlAttribute(): ?string
    {
        if (!$this->pgr_arquivo_path) {
            return null;
        }

        return S3Helper::temporaryUrl($this->pgr_arquivo_path, 10);
    }
}
