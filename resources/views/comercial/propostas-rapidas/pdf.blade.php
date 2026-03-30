<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Proposta Rápida</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; color: #16324f; font-size: 12px; margin: 0; background: #ffffff; }
    </style>
</head>
<body>
    @include('comercial.propostas-rapidas._documento', ['proposta' => $proposta, 'printMode' => true])
</body>
</html>
