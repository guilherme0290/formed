<?php

namespace App\Http\Controllers\Comercial;

use App\Http\Controllers\Controller;
use App\Models\Venda;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $empresaId = auth()->user()->empresa_id ?? null;

        $ranking = $this->rankingVendedores($empresaId);

        return view('comercial.dashboard', [
            'ranking' => $ranking,
        ]);

    }

    private function rankingVendedores(?int $empresaId): array
    {
        $hoje = Carbon::now();
        $inicioMes = $hoje->copy()->startOfMonth()->toDateString();
        $fimMes = $hoje->copy()->endOfMonth()->toDateString();

        // Soma do faturamento das vendas do mês, agrupado por vendedor (cliente->vendedor_id)
        $rows = Venda::query()
            ->join('clientes', 'clientes.id', '=', 'vendas.cliente_id')
            ->join('users', 'users.id', '=', 'clientes.vendedor_id')
            ->join('papeis', 'papeis.id', '=', 'users.papel_id')
            ->where('vendas.empresa_id', $empresaId)
            ->whereBetween(DB::raw('DATE(vendas.created_at)'), [$inicioMes, $fimMes])
            ->whereRaw('LOWER(papeis.nome) LIKE ?', ['%comercial%'])
            ->select(
                'users.id as vendedor_id',
                'users.name as vendedor_nome',
                DB::raw('SUM(vendas.total) as faturamento')
            )
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('faturamento')
            ->limit(3)
            ->get();

        $posicoes = [];
        $medalhas = ['ouro', 'prata', 'bronze'];

        foreach ($rows as $idx => $row) {
            $posicoes[] = [
                'posicao' => $idx + 1,
                'medalha' => $medalhas[$idx] ?? 'ouro',
                'nome' => $row->vendedor_nome,
                'faturamento' => (float) $row->faturamento,
            ];
        }

        return [
            'mesAtual' => $hoje->locale('pt_BR')->translatedFormat('F Y'),
            'itens' => $posicoes,
            'semDados' => $rows->isEmpty(),
        ];
    }
}
