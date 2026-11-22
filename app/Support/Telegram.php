<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin Telegram Bot API client. Stays inert (no-op) until a bot token is set in
 * config/services.php (`telegram.token`) via the TELEGRAM_BOT_TOKEN env var, so
 * the rest of the app works before the bot is connected.
 */
class Telegram
{
    public static function enabled(): bool
    {
        return ! empty(config('services.telegram.token'));
    }

    /** Send a message to the doctor's chat (or a given chat id). */
    public static function notify(string $text, ?array $replyMarkup = null, ?string $chatId = null): bool
    {
        $chatId ??= config('services.telegram.chat_id');
        if (! self::enabled() || empty($chatId)) {
            return false;
        }

        return self::call('sendMessage', array_filter([
            'chat_id'      => $chatId,
            'text'         => $text,
            'parse_mode'   => 'HTML',
            'reply_markup' => $replyMarkup ? json_encode($replyMarkup) : null,
        ]));
    }

    /** Low-level API call; returns true on ok. */
    public static function call(string $method, array $params = []): bool
    {
        if (! self::enabled()) {
            return false;
        }

        try {
            $res = Http::timeout(15)
                ->asForm()
                ->post('https://api.telegram.org/bot' . config('services.telegram.token') . '/' . $method, $params);

            return $res->successful() && ($res->json('ok') === true);
        } catch (\Throwable $e) {
            Log::warning('Telegram ' . $method . ' failed: ' . $e->getMessage());

            return false;
        }
    }

    /** Fetch updates (long-poll) for the polling command. */
    public static function getUpdates(int $offset = 0, int $timeout = 0): array
    {
        if (! self::enabled()) {
            return [];
        }

        try {
            $res = Http::timeout($timeout + 15)->get(
                'https://api.telegram.org/bot' . config('services.telegram.token') . '/getUpdates',
                ['offset' => $offset, 'timeout' => $timeout]
            );

            return $res->json('ok') ? ($res->json('result') ?? []) : [];
        } catch (\Throwable $e) {
            Log::warning('Telegram getUpdates failed: ' . $e->getMessage());

            return [];
        }
    }

    public static function answerCallback(string $callbackId, string $text = ''): bool
    {
        return self::call('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackId,
            'text'              => $text,
        ]));
    }
}
