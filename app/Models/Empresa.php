<?php

namespace App\Models;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = ['nome','cnpj','email','telefone','ativo'];
    protected $casts = ['ativo'=>'bool'];
}
