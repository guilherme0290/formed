<?php

namespace App\Models;

use App\Traits\HasRoles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'telefone',
        'documento',
        'password',
        'papel_id',
        'empresa_id',
        'cliente_id',
        'is_active',
        'ativo',            // ✅ para toggle de status (legado)
        'must_change_password',
        'lgpd_accepted_at',
        'proposta_desconto_max_percentual',
        'is_protected',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'ativo'             => 'boolean',   // ✅
            'last_login_at'     => 'datetime',  // ✅
            'is_active'         => 'boolean',
            'must_change_password' => 'boolean',
            'lgpd_accepted_at' => 'datetime',
            'proposta_desconto_max_percentual' => 'decimal:2',
            'is_protected' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $user): void {
            $actorId = auth()->id();

            if (app()->runningInConsole()) {
                return;
            }

            if ($user->is_protected && $actorId !== (int) $user->id) {
                throw new AuthorizationException('Não é permitido alterar um usuário protegido.');
            }
        });

        static::deleting(function (self $user): void {
            $actorId = auth()->id();

            if (app()->runningInConsole()) {
                return;
            }

            if ($user->is_protected && $actorId !== (int) $user->id) {
                throw new AuthorizationException('Não é permitido excluir um usuário protegido.');
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class, 'responsavel_id');
    }

    public function papel(): BelongsTo
    {
        return $this->belongsTo(Papel::class);
    }

    public function permissoesDiretas(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'user_permissao', 'user_id', 'permissao_id');
    }

    /**
     * Verifica se o usuário possui um determinado papel pelo NOME.
     *
     * Ex:
     *  $user->hasPapel('Master')
     *  $user->hasPapel(['Master', 'Operacional'])
     */
    public function hasPapel(string|array $nome): bool
    {
        if ($this->isSuperUser()) {
            $nomes = is_array($nome) ? $nome : [$nome];
            $nomesNormalizados = array_map(
                fn ($n) => mb_strtolower(trim((string) $n)),
                $nomes
            );

            if (in_array('master', $nomesNormalizados, true)) {
                return true;
            }
        }

        $papel = $this->papel; // já usa o relacionamento carregado, se tiver

        if (!$papel) {
            return false;
        }

        $atual = mb_strtolower(trim((string) $papel->nome));

        $nomes = is_array($nome) ? $nome : [$nome];
        $nomesNormalizados = array_map(
            fn ($n) => mb_strtolower(trim((string) $n)),
            $nomes
        );

        return in_array($atual, $nomesNormalizados, true);
    }

    /** Atalhos semânticos */
    public function isMaster(): bool
    {
        return $this->isSuperUser() || $this->hasPapel('Master');
    }

    public function isSuperUser(): bool
    {
        return (bool) $this->is_protected;
    }

    public function hasPermission(string|array $keys): bool
    {
        if ($this->isSuperUser()) {
            return true;
        }

        $keys = is_array($keys) ? $keys : [$keys];

        $viaPapel = $this->papel()
            ->whereHas('permissoes', fn ($q) => $q->whereIn('chave', $keys))
            ->exists();

        if ($viaPapel) {
            return true;
        }

        return $this->permissoesDiretas()
            ->whereIn('chave', $keys)
            ->exists();
    }

    public function isOperacional(): bool
    {
        return $this->hasPapel('Operacional');
    }

    public function isFinanceiro(): bool
    {
        return $this->hasPapel('Financeiro');
    }

    public function isCliente(): bool
    {
        return $this->hasPapel('Cliente');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }


}
