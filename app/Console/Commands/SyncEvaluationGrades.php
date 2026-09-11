<?php

namespace App\Console\Commands;

use App\Models\Evaluation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('evaluations:sync-grades')]
#[Description('Integrate existing evaluation results into the gradebook')]
class SyncEvaluationGrades extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(\App\Actions\SyncEvaluationGrades $syncGrades): int
    {
        Evaluation::query()->eachById(function ($evaluation) use ($syncGrades): void {
            $syncGrades->handle($evaluation);
        });
        $this->info('Evaluation grades synchronized.');

        return self::SUCCESS;
    }
}
