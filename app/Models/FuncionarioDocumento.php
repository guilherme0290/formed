<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuncionarioDocumento extends Model
{
    use HasFactory;

    protected $table = 'funcionario_documentos';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'funcionario_id',
        'tipo',
        'titulo',
        'arquivo_path',
        'valido_ate',
        'observacoes',
    ];

    protected $casts = [
        'valido_ate' => 'date',
    ];

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}

