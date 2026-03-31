<?php

namespace App\Models;

use App\Helpers\S3Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'pgr_public_token',
        'pgr_arquivo_nome',
        'pgr_arquivo_checksum',
        'funcoes',
        'obra_nome',
        'obra_cnpj_contratante',
        'obra_cei_cno',
        'obra_endereco',
        'prazo_dias',
        'duplicidade_confirmada',
        'duplicidade_confirmada_por',
        'duplicidade_confirmada_em',
        'duplicidade_referencia_tarefa_id',
    ];

    protected $casts = [
        'funcoes' => 'array',
        'duplicidade_confirmada' => 'boolean',
        'duplicidade_confirmada_em' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $pcmso) {
            if (filled($pcmso->pgr_arquivo_path) && blank($pcmso->pgr_public_token)) {
                $pcmso->pgr_public_token = static::gerarPgrPublicToken();
            }
        });

        static::updating(function (self $pcmso) {
            if (filled($pcmso->pgr_arquivo_path) && blank($pcmso->pgr_public_token)) {
                $pcmso->pgr_public_token = static::gerarPgrPublicToken();
            }
        });
    }

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

    public function duplicidadeConfirmadaPor()
    {
        return $this->belongsTo(User::class, 'duplicidade_confirmada_por');
    }

    public function duplicidadeReferenciaTarefa()
    {
        return $this->belongsTo(Tarefa::class, 'duplicidade_referencia_tarefa_id');
    }

    public function setCnpjAttribute($value)
    {
        $this->attributes['obra_cnpj_contratante'] = $value
            ? preg_replace('/\D+/', '', $value)
            : null;
    }

    public function getPgrArquivoUrlAttribute(): ?string
    {
        if (! $this->pgr_arquivo_path) {
            return null;
        }

        return S3Helper::temporaryUrl($this->pgr_arquivo_path, 10);
    }

    public function getPgrPublicLinkAttribute(): ?string
    {
        if (! $this->pgr_arquivo_path) {
            return null;
        }

        $token = $this->pgr_public_token ?: $this->garantirPgrPublicToken();

        return $token
            ? route('public.pcmso-pgr', ['token' => $token])
            : null;
    }

    public function pgrFoiEnviadoPeloCliente(): bool
    {
        return mb_strtolower(trim((string) $this->pgr_origem)) === 'arquivo_cliente';
    }

    private function garantirPgrPublicToken(): ?string
    {
        if (! $this->exists || ! $this->pgr_arquivo_path) {
            return null;
        }

        $token = static::gerarPgrPublicToken();

        $this->forceFill(['pgr_public_token' => $token])->saveQuietly();

        return $this->pgr_public_token;
    }

    public static function gerarPgrPublicToken(): string
    {
        do {
            $token = Str::random(8);
        } while (static::query()->where('pgr_public_token', $token)->exists());

        return $token;
    }
}
