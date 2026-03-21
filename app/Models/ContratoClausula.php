<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContratoClausula extends Model
{
    protected $table = 'contrato_clausulas';

    protected $fillable = [
        'empresa_id',
        'parent_id',
        'servico_tipo',
        'slug',
        'titulo',
        'ordem',
        'ordem_local',
        'html_template',
        'ativo',
        'versao',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
        'ordem_local' => 'integer',
        'versao' => 'integer',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
