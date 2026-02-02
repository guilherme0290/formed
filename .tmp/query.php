<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = DB::select("SELECT p.id, p.codigo, p.valor_total, p.status, p.vendedor_id, u.name AS vendedor, c.razao_social AS cliente FROM propostas p JOIN users u ON u.id=p.vendedor_id LEFT JOIN clientes c ON c.id=p.cliente_id WHERE UPPER(p.status)='PENDENTE' ORDER BY p.updated_at DESC");
print_r($rows);
?>
