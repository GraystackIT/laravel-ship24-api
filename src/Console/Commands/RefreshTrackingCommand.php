<?php

declare(strict_types=1);

namespace GraystackIT\Ship24\Console\Commands;

use GraystackIT\Ship24\Models\Ship24Tracking;
use GraystackIT\Ship24\Services\Ship24TrackingService;
use Illuminate\Console\Command;

class RefreshTrackingCommand extends Command
{
    protected $signature = 'ship24:refresh
        {id? : The ship24_trackings record ID to refresh (omit to refresh all)}';

    protected $description = 'Refresh tracking data from the Ship24 API';

    public function handle(Ship24TrackingService $service): int
    {
        $id = $this->argument('id');

        $query = Ship24Tracking::query();

        if ($id !== null) {
            $query->where('id', (int) $id);
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            $this->warn('No tracking records found.');

            return self::SUCCESS;
        }

        $bar     = $this->output->createProgressBar($records->count());
        $updated = 0;
        $failed  = 0;

        $bar->start();

        foreach ($records as $record) {
            try {
                $service->refresh($record);
                $updated++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("Failed #{$record->id} ({$record->tracking_number}): {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done. Updated: {$updated}, Failed: {$failed}.");

        return self::SUCCESS;
    }
}
