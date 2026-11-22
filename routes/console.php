<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Publish scheduled articles as soon as their time arrives. This command also
// auto-submits newly-published articles to the Google Indexing API.
Schedule::command('articles:publish-due')->everyMinute();

// Backstop: catch up any published-but-unsubmitted articles (only runs when the
// server cron calls `schedule:run`; the per-minute publish-due above is the
// primary path when the cron calls that command directly).
Schedule::command('articles:index-google')->hourly();

// Backstop: daily refresh of the index status of not-yet-indexed articles
// (the per-minute publish-due also does this a few at a time).
Schedule::command('articles:check-index')->daily();

// Appointment bot: process Telegram commands (close/open slots for walk-ins).
// Inert until TELEGRAM_BOT_TOKEN is set; no public webhook required.
Schedule::command('telegram:poll')->everyMinute()->withoutOverlapping();
