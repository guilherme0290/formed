<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Estado;


class Cidade extends Model
{
    use HasFactory;

    protected $table = 'cidades';

    protected $fillable = [
        'estado_id',
        'nome',
    ];

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }

    public function cidadesPorUf(string $uf)
    {
        $uf = strtoupper($uf);

        // 1) Descobre o estado no seu banco
        $estado = Estado::where('uf', $uf)->first();

        if (! $estado) {
            return response()->json([]);
        }

        // 2) Mapa das cidades do banco: nome normalizado -> id
        $cidadesDb = Cidade::where('estado_id', $estado->id)->get(['id', 'nome']);

        $map = [];
        foreach ($cidadesDb as $c) {
            $chave = mb_strtolower(trim($c->nome), 'UTF-8');
            $map[$chave] = $c->id;
        }

        // 3) Chama API do IBGE para essa UF
        $url = "https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios";
        $resp = Http::get($url);

        if (! $resp->successful()) {
            return response()->json([]);
        }

        $dados = $resp->json();

        $resultado = [];

        foreach ($dados as $m) {
            $nomeApi   = $m['nome'] ?? null;
            $chaveApi  = $nomeApi ? mb_strtolower(trim($nomeApi), 'UTF-8') : null;

            if (! $nomeApi || ! isset($map[$chaveApi])) {
                // não achou cidade equivalente no banco -> pula
                continue;
            }

            $resultado[] = [
                'id'   => $map[$chaveApi], // 👈 ID DA SUA TABELA "cidades"
                'nome' => $nomeApi,        // nome vindo da API do IBGE
            ];
        }

        return response()->json($resultado);
    }
}
