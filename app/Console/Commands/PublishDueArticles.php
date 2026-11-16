<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Support\GoogleIndexing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishDueArticles extends Command
{
    protected $signature = 'articles:publish-due';

    protected $description = 'Publish scheduled articles whose publish time has arrived';

    public function handle(): int
    {
        $count = Post::query()
            ->where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $this->info("Published {$count} scheduled article(s).");

        // Auto-submit newly-published (and any still-pending) articles to Google.
        // Runs every minute via the same cron, so future-scheduled posts are
        // sent to Google within a minute of going live. No-op unless enabled.
        try {
            $r = GoogleIndexing::submitPending(10);
            if (empty($r['skipped'])) {
                $this->info("Google Indexing: submitted {$r['ok']}, failed {$r['fail']}.");
            }
        } catch (\Throwable $e) {
            Log::warning('Google indexing auto-submit failed: ' . $e->getMessage());
        }

        // Auto-refresh the index status of not-yet-indexed articles. A few per
        // minute (older than ~20h) so every un-indexed article is re-checked
        // about once a day with no long request. No-op unless enabled.
        try {
            $c = GoogleIndexing::checkStale(5, 20);
            if (empty($c['skipped']) && ($c['ok'] + $c['fail']) > 0) {
                $this->info("Google index-check: refreshed {$c['ok']}, failed {$c['fail']}.");
            }
        } catch (\Throwable $e) {
            Log::warning('Google index-check failed: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
