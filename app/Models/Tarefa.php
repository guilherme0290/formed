<?php

namespace App\Models;

use App\Helpers\S3Helper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tarefa extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'tarefas';

    protected $fillable = [
        'empresa_id',
        'coluna_id',
        'responsavel_id',
        'cliente_id',
        'vendedor_snapshot_id',
        'vendedor_snapshot_nome',
        'funcionario_id',
        'observacao_interna',
        'motivo_exclusao',
        'excluido_por',
        'ordem',
        'servico_id',
        'titulo',
        'descricao',
        'prioridade',
        'status',
        'inicio_previsto',
        'fim_previsto',
        'finalizado_em',
        'data_prevista',
        'path_documento_cliente',
        'documento_token'
    ];

    protected $casts = [
        'inicio_previsto' => 'datetime',
        'fim_previsto'    => 'datetime',
        'finalizado_em'   => 'datetime',
        'data_prevista'   => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Tarefa $tarefa) {
            $tarefa->preencherVendedorSnapshot();

            if (empty($tarefa->coluna_id)) {
                return;
            }

            if (filled($tarefa->ordem) && (int) $tarefa->ordem > 0) {
                return;
            }

            $ultimaOrdem = static::query()
                ->where('coluna_id', $tarefa->coluna_id)
                ->max('ordem');

            $tarefa->ordem = ((int) $ultimaOrdem) + 1;
        });
    }

    // ===== RELACIONAMENTOS =====
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedorSnapshot(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_snapshot_id');
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

    public function excluidoPor()
    {
        return $this->belongsTo(User::class, 'excluido_por');
    }

    public function coluna()
    {
        return $this->belongsTo(KanbanColuna::class, 'coluna_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TarefaLog::class);
    }

    public function getArquivoClienteUrlAttribute(): ?string
    {
        if (!$this->path_documento_cliente) {
            return null;
        }

        return S3Helper::temporaryUrl($this->path_documento_cliente, 10);
    }

    public function getDocumentoLinkAttribute(): ?string
    {
        if (!$this->path_documento_cliente) {
            return null;
        }

        $token = $this->documento_token ?: $this->garantirDocumentoToken();

        return $token
            ? route('public.documento', ['token' => $token])
            : $this->arquivo_cliente_url;
    }

    public function getPacotePublicoLinkAttribute(): ?string
    {
        $token = $this->documento_token ?: $this->garantirDocumentoToken();

        return $token
            ? route('public.pacote', ['token' => $token])
            : null;
    }

    private function garantirDocumentoToken(): ?string
    {
        if (!$this->exists || !$this->path_documento_cliente) {
            return null;
        }

        $token = static::gerarDocumentoTokenCurto();

        $this->forceFill(['documento_token' => $token])->saveQuietly();

        return $this->documento_token;
    }

    public static function gerarDocumentoTokenCurto(): string
    {
        do {
            $token = Str::random(8);
        } while (static::query()->where('documento_token', $token)->exists());

        return $token;
    }

    public function ultimoLogMovimentacao(): ?TarefaLog
    {
        // helper opcional, se quiser usar no controller
        return $this->logs()->latest('created_at')->first();
    }

    public function anexos()
    {
        return $this->hasMany(Anexos::class);
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

    public function asoSolicitacao()
    {
        return $this->hasOne(AsoSolicitacoes::class, 'tarefa_id');
    }

    public function exameToxicologicoSolicitacao()
    {
        return $this->hasOne(ExameToxicologicoSolicitacao::class, 'tarefa_id');
    }

    private function preencherVendedorSnapshot(): void
    {
        if (filled($this->vendedor_snapshot_id) || filled($this->vendedor_snapshot_nome)) {
            return;
        }

        if (empty($this->cliente_id)) {
            return;
        }

        $cliente = $this->relationLoaded('cliente')
            ? $this->getRelation('cliente')
            : Cliente::query()->with('vendedor:id,name')->find($this->cliente_id);

        if (!$cliente) {
            return;
        }

        $this->vendedor_snapshot_id = $cliente->vendedor_id;
        $this->vendedor_snapshot_nome = optional($cliente->vendedor)->name;
    }











}
