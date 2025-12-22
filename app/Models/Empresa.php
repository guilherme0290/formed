<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Cidade;

class Empresa extends Model
{
    protected $fillable = ['nome','cnpj','email','telefone','endereco','cidade_id','ativo'];
    protected $casts = ['ativo'=>'bool'];

    public function cidade()
    {
        return $this->belongsTo(Cidade::class);
    }
}
