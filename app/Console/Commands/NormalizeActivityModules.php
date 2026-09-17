<?php

namespace App\Console\Commands;

use App\Models\LogActivity;
use App\Support\ActivityModule;
use Illuminate\Console\Command;

class NormalizeActivityModules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity:normalize-modules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalisasi nilai module pada log_activities menjadi label FE yang konsisten';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $rows = LogActivity::query()
            ->select('module')
            ->distinct()
            ->get();

        $updated = 0;
        foreach ($rows as $row) {
            $label = ActivityModule::label($row->module);

            if ($label === $row->module) {
                continue;
            }

            $updated += LogActivity::query()
                ->where('module', $row->module)
                ->update(['module' => $label]);
        }

        $this->info("Backfill selesai. Baris diupdate: {$updated}");

        return Command::SUCCESS;
    }
}