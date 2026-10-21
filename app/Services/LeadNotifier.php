<?php

namespace App\Services;

use App\Mail\LeadMail;
use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/*
 * Уведомления о новой заявке: письмо на адреса из настроек, сообщение в
 * Telegram в чат из настроек и запись в Битрикс24 заказчика (21.10). Ошибки не мешают сохранению заявки, они
 * пишутся в лог и возвращаются как текст (для кнопки проверки в админке).
 */
class LeadNotifier
{
    public function __construct(private readonly Bitrix24 $bitrix) {}

    /** @return array{email: string, telegram: string, bitrix: string} что получилось, человеческим языком */
    public function notify(Lead $lead, bool $test = false): array
    {
        return [
            'email' => $this->sendEmail($lead, $test),
            'telegram' => $this->sendTelegram($lead, $test),
            'bitrix' => $this->bitrix->send($lead, $test),
        ];
    }

    private function sendEmail(Lead $lead, bool $test): string
    {
        $emails = Setting::notifyEmails();
        if ($emails === []) {
            return 'Почта: адрес не задан, письмо не отправлено.';
        }
        try {
            Mail::to($emails)->send(new LeadMail($lead, $test));
            $mailer = config('mail.default');

            return 'Почта: письмо отправлено на '.implode(', ', $emails).' через '.$mailer
                .($mailer === 'log' ? ' (режим log: письмо записано в storage/logs/laravel.log, не отправлено по-настоящему)' : '').'.';
        } catch (Throwable $e) {
            Log::warning('Письмо о заявке не отправлено: '.$e->getMessage());

            return 'Почта: ошибка отправки: '.$e->getMessage();
        }
    }

    private function sendTelegram(Lead $lead, bool $test): string
    {
        $chatId = trim((string) Setting::get(Setting::TELEGRAM_CHAT_ID, ''));
        if ($chatId === '') {
            return 'Telegram: чат не задан, сообщение не отправлено.';
        }
        $token = (string) config('services.telegram.token');
        if ($token === '') {
            return 'Telegram: в .env нет TELEGRAM_BOT_TOKEN, сообщение не отправлено.';
        }
        $lines = [
            ($test ? '🔧 Проверка из админки. ' : '').'<b>Новая заявка с сайта ИКАН</b>',
            'Имя: '.e($lead->name),
            'Телефон: '.e($lead->phone),
        ];
        if ($lead->interest) {
            $lines[] = 'Что интересует: '.e($lead->interest);
        }
        $lines[] = $lead->sourceLabel().($lead->page ? ', страница '.e($lead->page) : '');
        $lines[] = $lead->consent_ads ? 'Реклама: согласен' : 'Реклама: без согласия';
        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => implode("\n", $lines),
                'parse_mode' => 'HTML',
            ]);
            if ($response->successful() && $response->json('ok') === true) {
                return 'Telegram: сообщение отправлено в чат '.$chatId.'.';
            }
            $description = (string) $response->json('description', $response->body());
            Log::warning('Telegram не принял сообщение о заявке: '.$description);

            return 'Telegram: не принято: '.$description;
        } catch (Throwable $e) {
            Log::warning('Telegram недоступен: '.$e->getMessage());

            return 'Telegram: ошибка связи: '.$e->getMessage();
        }
    }

    /**
     * Чаты, которые писали боту: чтобы узнать chat id без ручного копания.
     *
     * @return array<int, array{id: int|string, title: string}>|string список или текст ошибки
     */
    public function telegramChats(): array|string
    {
        $token = (string) config('services.telegram.token');
        if ($token === '') {
            return 'В .env нет TELEGRAM_BOT_TOKEN.';
        }
        try {
            $response = Http::timeout(10)->get("https://api.telegram.org/bot{$token}/getUpdates", ['limit' => 100]);
            if (! $response->successful() || $response->json('ok') !== true) {
                return 'Telegram ответил: '.(string) $response->json('description', $response->body());
            }
            $chats = [];
            foreach ((array) $response->json('result', []) as $update) {
                $chat = $update['message']['chat'] ?? $update['my_chat_member']['chat'] ?? $update['channel_post']['chat'] ?? null;
                if (! $chat) {
                    continue;
                }
                $title = $chat['title'] ?? trim(($chat['first_name'] ?? '').' '.($chat['last_name'] ?? ''));
                if (! empty($chat['username'])) {
                    $title .= ' (@'.$chat['username'].')';
                }
                $chats[$chat['id']] = ['id' => $chat['id'], 'title' => trim($title) ?: 'чат '.$chat['id']];
            }

            return array_values($chats);
        } catch (Throwable $e) {
            return 'Ошибка связи с Telegram: '.$e->getMessage();
        }
    }
}
