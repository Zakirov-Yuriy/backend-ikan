<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Setting;
use App\Services\Bitrix24;
use App\Services\LeadNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* Настройки: телефоны и ссылки соцсетей на сайте, куда слать уведомления о заявках */
class SettingsController extends Controller
{
    public function __construct(private readonly LeadNotifier $notifier, private readonly Bitrix24 $bitrix) {}

    public function edit(): View
    {
        return view('admin.settings.form', [
            'phone' => Setting::get(Setting::PHONE, Setting::DEFAULT_PHONE),
            'phone2' => Setting::phone2(),
            'socials' => collect(Setting::SOCIALS)->map(fn ($item, $name) => $item + ['value' => Setting::socials()[$name]]),
            'notifyEmails' => Setting::get(Setting::NOTIFY_EMAILS, ''),
            'telegramChatId' => Setting::get(Setting::TELEGRAM_CHAT_ID, ''),
            'telegramToken' => (string) config('services.telegram.token') !== '',
            'mailer' => config('mail.default'),
            'chats' => session('telegram_chats'),
            'bitrixWebhook' => Setting::get(Setting::BITRIX_WEBHOOK, ''),
            'bitrixFromEnv' => trim((string) Setting::get(Setting::BITRIX_WEBHOOK, '')) === '' && trim((string) config('services.bitrix24.webhook')) !== '',
            'bitrixEntity' => Setting::bitrixEntity(),
            'bitrixAssigned' => Setting::get(Setting::BITRIX_ASSIGNED, ''),
            'bitrixCategory' => Setting::get(Setting::BITRIX_CATEGORY, ''),
            'bitrixStage' => Setting::get(Setting::BITRIX_STAGE, ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[\d\s()\-]+$/'],
            'phone_2' => ['nullable', 'string', 'max:30', 'regex:/^\+?[\d\s()\-]+$/'],
            'social_max' => ['nullable', 'string', 'max:300', 'url:http,https'],
            'social_telegram' => ['nullable', 'string', 'max:300', 'url:http,https'],
            'social_vk' => ['nullable', 'string', 'max:300', 'url:http,https'],
            'notify_emails' => ['nullable', 'string', 'max:500'],
            'telegram_chat_id' => ['nullable', 'string', 'max:50', 'regex:/^-?\d+$/'],
            'bitrix_webhook' => ['nullable', 'string', 'max:500', 'url:https', 'regex:#/rest/\d+/[^/]+/?$#'],
            'bitrix_entity' => ['nullable', 'in:lead,deal'],
            'bitrix_assigned' => ['nullable', 'integer', 'min:1'],
            'bitrix_category' => ['nullable', 'integer', 'min:0'],
            'bitrix_stage' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_:]+$/'],
        ], [
            'phone.regex' => 'Телефон: цифры, пробелы, скобки, дефис и плюс в начале, например +7 (991) 255-29-00.',
            'phone_2.regex' => 'Второй телефон: цифры, пробелы, скобки, дефис и плюс в начале, например +7 (991) 587-24-57.',
            'url' => 'Ссылка :attribute должна начинаться с https:// или http://.',
            'telegram_chat_id.regex' => 'Chat id в Telegram это число, например 123456789 или -1001234567890. Нажмите «Найти chat id» ниже.',
            'bitrix_webhook.url' => 'Ссылка вебхука Битрикс24 должна начинаться с https://.',
            'bitrix_webhook.regex' => 'Это не похоже на ссылку входящего вебхука. Она выглядит так: https://ваш-портал.bitrix24.ru/rest/1/abc123def456/',
            'bitrix_stage.regex' => 'Код этапа из Битрикс24: латинские буквы, цифры, двоеточие, например NEW или C2:NEW.',
        ], [
            'phone' => '«телефон»',
            'phone_2' => '«второй телефон»',
            'social_max' => '«MAX»',
            'social_telegram' => '«Telegram»',
            'social_vk' => '«ВКонтакте»',
            'notify_emails' => '«почта для уведомлений»',
            'telegram_chat_id' => '«chat id в Telegram»',
            'bitrix_webhook' => '«ссылка вебхука Битрикс24»',
            'bitrix_assigned' => '«ответственный в Битрикс24»',
            'bitrix_category' => '«воронка сделок»',
            'bitrix_stage' => '«этап сделки»',
        ]);
        if (strlen(preg_replace('/\D/', '', $data['phone'])) < 6) {
            return back()->withInput()->withErrors('В телефоне слишком мало цифр.');
        }
        $phone2 = trim((string) ($data['phone_2'] ?? ''));
        if ($phone2 !== '' && strlen(preg_replace('/\D/', '', $phone2)) < 6) {
            return back()->withInput()->withErrors('Во втором телефоне слишком мало цифр.');
        }

        $raw = (string) ($data['notify_emails'] ?? '');
        $bad = array_filter(preg_split('/[\s,;]+/', $raw) ?: [], fn ($e) => $e !== '' && ! filter_var($e, FILTER_VALIDATE_EMAIL));
        if ($bad !== []) {
            return back()->withInput()->withErrors('Это не похоже на почту: '.implode(', ', $bad));
        }

        Setting::set(Setting::PHONE, trim($data['phone']));
        Setting::set(Setting::PHONE_2, $phone2);
        foreach (Setting::SOCIALS as $item) {
            Setting::set($item['key'], trim((string) ($data[$item['key']] ?? '')));
        }
        Setting::set(Setting::NOTIFY_EMAILS, trim($raw));
        Setting::set(Setting::TELEGRAM_CHAT_ID, trim((string) ($data['telegram_chat_id'] ?? '')));
        Setting::set(Setting::BITRIX_WEBHOOK, trim((string) ($data['bitrix_webhook'] ?? '')));
        Setting::set(Setting::BITRIX_ENTITY, $data['bitrix_entity'] ?? Setting::BITRIX_LEAD);
        Setting::set(Setting::BITRIX_ASSIGNED, (string) ($data['bitrix_assigned'] ?? ''));
        Setting::set(Setting::BITRIX_CATEGORY, (string) ($data['bitrix_category'] ?? ''));
        Setting::set(Setting::BITRIX_STAGE, trim((string) ($data['bitrix_stage'] ?? '')));

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

        return redirect()->route('admin.settings.edit')->with('success', $result['email'].' '.$result['telegram'].' '.$result['bitrix']);
    }

    /* Проверка связи с Битрикс24 без создания записей */
    public function bitrixCheck(): RedirectResponse
    {
        return redirect()->route('admin.settings.edit')->with('success', $this->bitrix->check());
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
