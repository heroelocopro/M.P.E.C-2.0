<?php

namespace App\Console\Commands;

use App\Actions\ProcessAcademicClosures;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('academics:process-closures {--date= : Fecha de proceso en formato YYYY-MM-DD}')]
#[Description('Envía recordatorios y cierra automáticamente los periodos académicos vencidos')]
class ProcessAcademicClosuresCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ProcessAcademicClosures $closures): int
    {
        $date = $this->option('date') ? Carbon::parse((string) $this->option('date')) : null;
        $result = $closures->handle($date);

        $this->info("Recordatorios: {$result['reminders']}; periodos cerrados: {$result['periodsClosed']}; años cerrados: {$result['yearsClosed']}.");

        return self::SUCCESS;
    }
}
