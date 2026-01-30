<?php

namespace App\Console;

use App\Console\Commands\GerarEsocialMensal;
use App\Console\Commands\ImportarClausulasContrato;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        GerarEsocialMensal::class,
        ImportarClausulasContrato::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('esocial:gerar-vendas-mensais')
            ->monthlyOn(1, '02:00');
    }
}
