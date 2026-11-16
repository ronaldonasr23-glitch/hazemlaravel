<?php

namespace App\Console\Commands;

use App\Support\GoogleIndexing;
use Illuminate\Console\Command;

class CheckIndexArticles extends Command
{
    protected $signature = 'articles:check-index {--limit=200}';

    protected $description = 'Refresh the Google index status of not-yet-indexed articles';

    public function handle(): int
    {
        if (! GoogleIndexing::enabled()) {
            $this->warn('Google Indexing API is not enabled/configured.');

            return self::SUCCESS;
        }

        // staleHours = 0 → re-check every not-yet-indexed article now.
        $r = GoogleIndexing::checkStale((int) $this->option('limit'), 0);

        $this->info("Refreshed {$r['ok']}, failed {$r['fail']}.");

        return self::SUCCESS;
    }
}
