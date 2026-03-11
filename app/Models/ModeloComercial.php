<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeloComercial extends Model
{
    protected $table = 'modelos_comerciais';

    protected $fillable = [
        'empresa_id',
        'segmento',
        'nome_modelo',
        'titulo',
        'intro_1',
        'intro_2',
        'mensagem_principal',
        'comissao_vendedor',
        'contato_email',
        'contato_telefone',
        'catalogo_preco',
        'beneficios',
        'rodape',
        'usar_todos_exames',
        'esocial_descricao',
        'ativo',
        'layout',
    ];

    protected $casts = [
        'ativo' => 'bool',
        'usar_todos_exames' => 'bool',
        'comissao_vendedor' => 'decimal:2',
        'layout' => 'array',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function itens()
    {
        return $this->hasMany(ModeloComercialItem::class, 'modelo_comercial_id');
    }

    public function precos()
    {
        return $this->hasMany(ModeloComercialPreco::class, 'modelo_comercial_id');
    }

    public function exames()
    {
        return $this->hasMany(ModeloComercialExame::class, 'modelo_comercial_id');
    }

    public function treinamentos()
    {
        return $this->hasMany(ModeloComercialTreinamento::class, 'modelo_comercial_id');
    }

    public function tabelas()
    {
        return $this->hasMany(ModeloComercialTabela::class, 'modelo_comercial_id');
    }
}
