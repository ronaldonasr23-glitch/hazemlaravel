<?php

namespace App\Console\Commands;

use App\Support\ArticleImporter;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportArticles extends Command
{
    protected $signature = 'articles:import {file : Path to the CSV of articles}
        {--interval=1 : Days between each publish (1=daily, 2=every other day)}
        {--start= : First publish datetime (e.g. "2026-11-12 09:00"); defaults to tomorrow 09:00}
        {--images= : Optional path to a ZIP of thumbnails named 1,2,3… matching CSV rows}';

    protected $description = 'Import articles from a CSV and schedule them to auto-publish at a fixed cadence';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $start = $this->option('start')
            ? Carbon::parse($this->option('start'))
            : now()->addDay()->setTime(9, 0);

        $images = $this->option('images');
        if ($images && ! is_file($images)) {
            $this->error("Images ZIP not found: {$images}");

            return self::FAILURE;
        }

        $r = ArticleImporter::fromCsv($file, (int) $this->option('interval'), $start, $images ?: null);

        $range = $r['first'] && $r['last']
            ? $r['first']->format('Y-m-d H:i') . ' → ' . $r['last']->format('Y-m-d H:i')
            : '—';

        $this->info("Created: {$r['created']}  Skipped: {$r['skipped']}  Publishing: {$range}");

        return self::SUCCESS;
    }
}
