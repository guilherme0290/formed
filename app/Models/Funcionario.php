<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    use HasFactory;

    protected $table = 'funcionarios';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'nome',
        'cpf',
        'rg',
        'data_nascimento',
        'data_admissao',
        'funcao',
        'treinamento_nr',
        'exame_admissional',
        'exame_periodico',
        'exame_demissional',
        'exame_mudanca_funcao',
        'exame_retorno_trabalho',
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
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
