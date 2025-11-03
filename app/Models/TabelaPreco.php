<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TabelaPreco extends Model {
    protected $table = 'tabela_precos';
    protected $fillable = ['empresa_id','cliente_id','tipo','servico_id','combo_id','codigo','descricao','preco','vigencia_inicio','vigencia_fim','ativo'];
    protected $casts = ['preco'=>'decimal:2','vigencia_inicio'=>'date','vigencia_fim'=>'date','ativo'=>'boolean'];

    public function empresa(){ return $this->belongsTo(Empresa::class); }
    public function cliente(){ return $this->belongsTo(Cliente::class); }
    public function servico(){ return $this->belongsTo(Servico::class); }
    public function combo(){ return $this->belongsTo(Combo::class); }

    // Busca vigente por data
    public function scopeVigenteEm($q, $data) {
        return $q->where('vigencia_inicio','<=',$data)
            ->where(function($qq) use ($data){
                $qq->whereNull('vigencia_fim')->orWhere('vigencia_fim','>=',$data);
            });
    }
}
