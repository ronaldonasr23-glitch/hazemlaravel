<?php

namespace App\Console\Commands;

use App\Support\GoogleIndexing;
use Illuminate\Console\Command;

class IndexGoogleArticles extends Command
{
    protected $signature = 'articles:index-google {--all : Resubmit every published article, even if already submitted}';

    protected $description = 'Submit published articles to the Google Indexing API';

    public function handle(): int
    {
        if (! GoogleIndexing::enabled()) {
            $this->warn('Google Indexing API is not enabled/configured.');

            return self::SUCCESS;
        }

        $result = $this->option('all')
            ? GoogleIndexing::submitAll()
            : GoogleIndexing::submitPending(200);

        $this->info("Submitted {$result['ok']}, failed {$result['fail']}.");

        return self::SUCCESS;
    }
}
