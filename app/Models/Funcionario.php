<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funcionario extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'funcionarios';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'nome',
        'cpf',
        'rg',
        'data_nascimento',
        'data_admissao',
        'funcao_id',
        'treinamento_nr',
        'exame_admissional',
        'exame_periodico',
        'exame_demissional',
        'exame_mudanca_funcao',
        'exame_retorno_trabalho',
        'celular',
        'setor',
        'ativo',
    ];

    protected $casts = [
        'data_nascimento'       => 'date',
        'data_admissao'         => 'date',
        'treinamento_nr'        => 'boolean',
        'exame_admissional'     => 'boolean',
        'exame_periodico'       => 'boolean',
        'exame_demissional'     => 'boolean',
        'exame_mudanca_funcao'  => 'boolean',
        'exame_retorno_trabalho'=> 'boolean',
        'ativo'                  => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function setCpfAttribute($value)
    {
        $this->attributes['cpf'] = $value
            ? preg_replace('/\D+/', '', $value)
            : null;
    }

    public function funcao()
    {
        return $this->belongsTo(\App\Models\Funcao::class, 'funcao_id');
    }

    public function getFuncaoNomeAttribute()
    {
        return $this->funcao ? $this->funcao->nome : '—';
    }

    public function documentos()
    {
        return $this->hasMany(FuncionarioDocumento::class);
    }
}
