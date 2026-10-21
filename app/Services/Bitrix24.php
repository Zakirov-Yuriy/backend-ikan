<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/*
 * Заявки с сайта в CRM заказчика, Битрикс24 (Саша, 21.10). Связь через
 * входящий вебхук: администратор Битрикс24 создаёт его с правами «CRM» и
 * вставляет ссылку в «Настройки» админки (или в переменную BITRIX24_WEBHOOK
 * на сервере). Что создавать, лид или сделку с контактом, тоже в настройках:
 * в классическом режиме CRM лиды, в простом (без лидов) сделки.
 *
 * Методы REST: crm.lead.add, crm.contact.add, crm.deal.add,
 * crm.settings.mode.get (проверка связи). Ошибка Битрикс24 не мешает
 * сохранению заявки: она пишется в лог и возвращается текстом.
 */
class Bitrix24
{
    /* Ссылка вебхука: из админки, если там пусто, из .env (BITRIX24_WEBHOOK) */
    public function webhook(): string
    {
        $url = trim((string) Setting::get(Setting::BITRIX_WEBHOOK, ''));
        if ($url === '') {
            $url = trim((string) config('services.bitrix24.webhook'));
        }

        return rtrim($url, '/');
    }

    /** Отправить заявку. Возвращает текст для админки: что получилось */
    public function send(Lead $lead, bool $test = false): string
    {
        if ($this->webhook() === '') {
            return 'Битрикс24: ссылка вебхука не задана, заявка не отправлена.';
        }
        try {
            if (Setting::bitrixEntity() === Setting::BITRIX_DEAL) {
                $contactId = $this->call('crm.contact.add', ['fields' => $this->contactFields($lead)]);
                $dealId = $this->call('crm.deal.add', ['fields' => $this->dealFields($lead, (int) $contactId, $test), 'params' => ['REGISTER_SONET_EVENT' => 'Y']]);

                return "Битрикс24: создана сделка №{$dealId} и контакт №{$contactId}.";
            }
            $leadId = $this->call('crm.lead.add', ['fields' => $this->leadFields($lead, $test), 'params' => ['REGISTER_SONET_EVENT' => 'Y']]);

            return "Битрикс24: создан лид №{$leadId}.";
        } catch (Throwable $e) {
            Log::warning('Заявка не ушла в Битрикс24: '.$e->getMessage());

            return 'Битрикс24: не отправлено: '.$e->getMessage();
        }
    }

    /** Проверка связи без создания записей: режим CRM у заказчика */
    public function check(): string
    {
        if ($this->webhook() === '') {
            return 'Битрикс24: сначала вставьте ссылку вебхука и сохраните настройки.';
        }
        try {
            $mode = (int) $this->call('crm.settings.mode.get', []);
            $name = $mode === 2 ? 'простой, без лидов: выберите «Сделка и контакт»' : 'классический, с лидами: подойдёт «Лид»';

            return "Битрикс24: связь есть, права на CRM есть. Режим CRM: {$name}.";
        } catch (Throwable $e) {
            return 'Битрикс24: связи нет: '.$e->getMessage();
        }
    }

    private function call(string $method, array $payload): mixed
    {
        $response = Http::timeout(10)->acceptJson()->asJson()->post($this->webhook().'/'.$method.'.json', $payload);
        $error = $response->json('error_description') ?: $response->json('error');
        if ($error || ! $response->successful()) {
            throw new RuntimeException($error ?: 'ответ '.$response->status());
        }

        return $response->json('result');
    }

    private function title(Lead $lead, bool $test): string
    {
        return ($test ? 'Проверка из админки: ' : '').'Заявка с сайта ИКАН: '.$lead->name;
    }

    /* Подробности заявки одним текстом: что интересует, откуда, согласие на рекламу */
    private function comments(Lead $lead): string
    {
        $lines = [];
        if ($lead->interest) {
            $lines[] = 'Что интересует: '.$lead->interest;
        }
        $lines[] = 'Откуда: '.$lead->sourceLabel().($lead->page ? ', страница '.rtrim((string) config('app.site_url'), '/').$lead->page : '');
        $lines[] = $lead->consent_ads ? 'Реклама: согласен на рекламные и информационные сообщения' : 'Реклама: без согласия';

        return implode("\n", $lines);
    }

    private function common(Lead $lead): array
    {
        $fields = ['SOURCE_ID' => 'WEB', 'SOURCE_DESCRIPTION' => 'Сайт ИКАН, '.$lead->sourceLabel()];
        $assigned = Setting::bitrixAssigned();
        if ($assigned) {
            $fields['ASSIGNED_BY_ID'] = $assigned;
        }

        return $fields;
    }

    private function phone(Lead $lead): array
    {
        return [['VALUE' => $lead->phone, 'VALUE_TYPE' => 'WORK']];
    }

    private function leadFields(Lead $lead, bool $test): array
    {
        return [
            'TITLE' => $this->title($lead, $test),
            'NAME' => $lead->name,
            'PHONE' => $this->phone($lead),
            'COMMENTS' => $this->comments($lead),
            ...$this->common($lead),
        ];
    }

    private function contactFields(Lead $lead): array
    {
        return ['NAME' => $lead->name, 'PHONE' => $this->phone($lead), ...$this->common($lead)];
    }

    private function dealFields(Lead $lead, int $contactId, bool $test): array
    {
        $fields = [
            'TITLE' => $this->title($lead, $test),
            'CONTACT_ID' => $contactId,
            'COMMENTS' => $this->comments($lead),
            ...$this->common($lead),
        ];
        $category = Setting::bitrixCategory();
        if ($category !== null) {
            $fields['CATEGORY_ID'] = $category;
        }
        $stage = trim((string) Setting::get(Setting::BITRIX_STAGE, ''));
        if ($stage !== '') {
            $fields['STAGE_ID'] = $stage;
        }

        return $fields;
    }
}
