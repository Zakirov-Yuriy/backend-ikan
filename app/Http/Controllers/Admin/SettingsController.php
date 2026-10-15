<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Setting;
use App\Services\LeadNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* Настройки: телефон на сайте, куда слать уведомления о заявках */
class SettingsController extends Controller
{
    public function __construct(private readonly LeadNotifier $notifier) {}

    public function edit(): View
    {
        return view('admin.settings.form', [
            'phone' => Setting::get(Setting::PHONE, Setting::DEFAULT_PHONE),
            'notifyEmails' => Setting::get(Setting::NOTIFY_EMAILS, ''),
            'telegramChatId' => Setting::get(Setting::TELEGRAM_CHAT_ID, ''),
            'telegramToken' => (string) config('services.telegram.token') !== '',
            'mailer' => config('mail.default'),
            'chats' => session('telegram_chats'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[\d\s()\-]+$/'],
            'notify_emails' => ['nullable', 'string', 'max:500'],
            'telegram_chat_id' => ['nullable', 'string', 'max:50', 'regex:/^-?\d+$/'],
        ], [
            'phone.regex' => 'Телефон: цифры, пробелы, скобки, дефис и плюс в начале, например +7 (991) 255-29-00.',
            'telegram_chat_id.regex' => 'Chat id в Telegram это число, например 123456789 или -1001234567890. Нажмите «Найти chat id» ниже.',
        ], [
            'phone' => '«телефон»',
            'notify_emails' => '«почта для уведомлений»',
            'telegram_chat_id' => '«chat id в Telegram»',
        ]);
        if (strlen(preg_replace('/\D/', '', $data['phone'])) < 6) {
            return back()->withInput()->withErrors('В телефоне слишком мало цифр.');
        }

        $raw = (string) ($data['notify_emails'] ?? '');
        $bad = array_filter(preg_split('/[\s,;]+/', $raw) ?: [], fn ($e) => $e !== '' && ! filter_var($e, FILTER_VALIDATE_EMAIL));
        if ($bad !== []) {
            return back()->withInput()->withErrors('Это не похоже на почту: '.implode(', ', $bad));
        }

        Setting::set(Setting::PHONE, trim($data['phone']));
        Setting::set(Setting::NOTIFY_EMAILS, trim($raw));
        Setting::set(Setting::TELEGRAM_CHAT_ID, trim((string) ($data['telegram_chat_id'] ?? '')));

        return redirect()->route('admin.settings.edit')->with('success', 'Настройки сохранены.');
    }

    /* Проверочное уведомление на текущие настройки */
    public function test(): RedirectResponse
    {
        $lead = new Lead([
            'name' => 'Проверка из админки',
            'phone' => '+7 000 000 00 00',
            'interest' => 'Проверка уведомлений',
            'source' => Lead::SOURCE_POPUP,
            'page' => '/',
        ]);
        $lead->created_at = now();
        $result = $this->notifier->notify($lead, test: true);

        return redirect()->route('admin.settings.edit')->with('success', $result['email'].' '.$result['telegram']);
    }

    /* Список чатов, которые писали боту: чтобы взять chat id */
    public function chats(): RedirectResponse
    {
        $chats = $this->notifier->telegramChats();
        if (is_string($chats)) {
            return redirect()->route('admin.settings.edit')->withErrors($chats);
        }
        if ($chats === []) {
            return redirect()->route('admin.settings.edit')->withErrors('Боту пока никто не писал. Откройте бота в Telegram, нажмите «Start» или напишите ему любое слово и повторите.');
        }

        return redirect()->route('admin.settings.edit')->with('telegram_chats', $chats);
    }
}
