<?php

namespace App\Console\Commands;

use App\Support\RedirectImporter;
use Illuminate\Console\Command;

class ImportRedirects extends Command
{
    protected $signature = 'redirects:import {file : Path to the CSV of old URLs} {--no-overwrite : Do not update existing redirects}';

    protected $description = 'Import old URLs from a CSV and auto-create 301 redirects to the matching pages';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $r = RedirectImporter::fromCsv($file, ! $this->option('no-overwrite'));

        $this->info("Created: {$r['created']}  Updated: {$r['updated']}  Skipped (already correct): {$r['skipped']}");

        return self::SUCCESS;
    }
}
