<?php

namespace App\Console\Commands;

use App\Models\AppointmentSlot;
use App\Support\Appointments;
use App\Support\Telegram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Poll the Telegram bot for the doctor's commands and act on them — mainly
 * closing/opening appointment slots for walk-ins, so the website stays in sync.
 * Runs every minute from the scheduler (routes/console.php), so no public
 * webhook is needed. Inert until TELEGRAM_BOT_TOKEN is set.
 *
 * Commands (only from the configured chat):
 *   /slots   — list the next open slots, each with a "Close" button
 *   /closed  — list closed slots, each with an "Open" button
 */
class TelegramPoll extends Command
{
    protected $signature = 'telegram:poll';

    protected $description = 'Process Telegram bot updates (close/open appointment slots for walk-ins)';

    public function handle(): int
    {
        if (! Telegram::enabled()) {
            return self::SUCCESS;
        }

        $offset = (int) Cache::get('telegram_update_offset', 0);
        $updates = Telegram::getUpdates($offset);
        $allowed = (string) config('services.telegram.chat_id');

        foreach ($updates as $u) {
            $offset = max($offset, ($u['update_id'] ?? 0) + 1);

            if (isset($u['callback_query'])) {
                $this->handleCallback($u['callback_query'], $allowed);
            } elseif (isset($u['message']['text'])) {
                $this->handleText($u['message'], $allowed);
            }
        }

        Cache::put('telegram_update_offset', $offset, now()->addDays(7));

        return self::SUCCESS;
    }

    private function fromAllowedChat(array $chatId, string $allowed): bool
    {
        return $allowed === '' ? true : ((string) $chatId === $allowed);
    }

    private function handleText(array $message, string $allowed): void
    {
        $chatId = (string) ($message['chat']['id'] ?? '');
        if ($allowed !== '' && $chatId !== $allowed) {
            return; // ignore everyone but the doctor
        }

        $cmd = strtolower(trim(explode(' ', trim($message['text']))[0]));
        $cmd = explode('@', $cmd)[0]; // strip @botname

        match ($cmd) {
            '/start', '/help' => Telegram::notify(
                "👋 Appointment bot ready.\n\n/slots — open slots (tap to close)\n/closed — closed slots (tap to re-open)",
                null, $chatId
            ),
            '/slots'  => $this->sendOpenSlots($chatId),
            '/closed' => $this->sendClosedSlots($chatId),
            default   => null,
        };
    }

    private function sendOpenSlots(string $chatId): void
    {
        $slots = AppointmentSlot::with('type')->bookable()
            ->orderBy('starts_at')->limit(20)->get();

        if ($slots->isEmpty()) {
            Telegram::notify('No open slots.', null, $chatId);

            return;
        }

        $rows = $slots->map(fn ($s) => [[
            'text'          => '❌ ' . $s->starts_at->format('D j M H:i') . ' · ' . ($s->type->name ?? ''),
            'callback_data' => 'close:' . $s->id,
        ]])->values()->all();

        Telegram::notify('🟢 <b>Open slots</b> — tap one to close it:', ['inline_keyboard' => $rows], $chatId);
    }

    private function sendClosedSlots(string $chatId): void
    {
        $slots = AppointmentSlot::with('type')->where('status', 'closed')
            ->where('starts_at', '>', now())->orderBy('starts_at')->limit(20)->get();

        if ($slots->isEmpty()) {
            Telegram::notify('No closed slots.', null, $chatId);

            return;
        }

        $rows = $slots->map(fn ($s) => [[
            'text'          => '♻️ ' . $s->starts_at->format('D j M H:i') . ' · ' . ($s->type->name ?? ''),
            'callback_data' => 'open:' . $s->id,
        ]])->values()->all();

        Telegram::notify('🔴 <b>Closed slots</b> — tap one to re-open it:', ['inline_keyboard' => $rows], $chatId);
    }

    private function handleCallback(array $cb, string $allowed): void
    {
        $chatId = (string) ($cb['message']['chat']['id'] ?? '');
        if ($allowed !== '' && $chatId !== $allowed) {
            Telegram::answerCallback($cb['id'] ?? '', 'Not allowed.');

            return;
        }

        [$action, $id] = array_pad(explode(':', $cb['data'] ?? ''), 2, null);
        $slot = AppointmentSlot::with('type')->find((int) $id);

        if (! $slot) {
            Telegram::answerCallback($cb['id'] ?? '', 'Slot not found.');

            return;
        }

        $label = $slot->starts_at->format('D j M H:i') . ' · ' . ($slot->type->name ?? '');

        if ($action === 'close') {
            Appointments::closeSlot($slot);
            Telegram::answerCallback($cb['id'] ?? '', 'Closed.');
            Telegram::notify("✅ Closed: {$label}", null, $chatId);
        } elseif ($action === 'open') {
            Appointments::openSlot($slot);
            Telegram::answerCallback($cb['id'] ?? '', 'Re-opened.');
            Telegram::notify("✅ Re-opened: {$label}", null, $chatId);
        } else {
            Telegram::answerCallback($cb['id'] ?? '');
        }
    }
}
