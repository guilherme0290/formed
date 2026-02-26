<?php

namespace App\Models;

use App\Traits\HasRoles;
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
        ];
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
        return $this->hasPapel('Master');
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
