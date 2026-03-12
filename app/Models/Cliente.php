<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Cidade;
use App\Models\Funcao;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'empresa_id',
        'vendedor_id',
        'tipo_pessoa',
        'razao_social',
        'nome_fantasia',
        'cpf',
        'cnpj',
        'email',
        'telefone',
        'contato',
        'telefone_2',
        'observacao',
        'tipo_cliente',
        'cep',
        'endereco',
        'numero',
        'bairro',
        'complemento',
        'cidade_id',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function getDocumentoPrincipalAttribute(): ?string
    {
        $documento = $this->cpf ?: $this->cnpj;

        if (!$documento) {
            return null;
        }

        return $this->formatDocumento($documento);
    }

    public function getDocumentoLabelAttribute(): string
    {
        return $this->cpf ? 'CPF' : 'CNPJ';
    }

    public function cidade()
    {
        return $this->belongsTo(Cidade::class);
    }

    public function userCliente()
    {
        return $this->hasOne(User::class, 'cliente_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function unidadesPermitidas(): BelongsToMany
    {
        return $this->belongsToMany(
            UnidadeClinica::class,
            'cliente_unidade_permitidas',
            'cliente_id',
            'unidade_id'
        )->withTimestamps();
    }

    public function funcoes(): BelongsToMany
    {
        return $this->belongsToMany(
            Funcao::class,
            'cliente_funcoes',
            'cliente_id',
            'funcao_id'
        )->withTimestamps();
    }

    private function formatDocumento(?string $documento): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $documento) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits);
        }

        if (strlen($digits) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits);
        }

        return $documento;
    }
}

