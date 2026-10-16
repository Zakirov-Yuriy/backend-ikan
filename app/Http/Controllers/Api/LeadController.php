<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadNotifier;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

/* Приём заявки с форм сайта: POST /api/requests */
class LeadController extends Controller
{
    public function __construct(private readonly LeadNotifier $notifier) {}

    public function store(Request $request): JsonResponse
    {
        /* Поле-ловушка для роботов: обычный посетитель его не видит и не заполняет */
        if (trim((string) $request->input('website', '')) !== '') {
            return response()->json(['data' => ['id' => 0]], 201);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['bail', 'required', 'string', 'min:5', 'max:50', 'regex:/^[\d\s()+\-]+$/', $this->phoneLength(...)],
            'interest' => ['nullable', 'string', 'max:2000'],
            'source' => ['required', Rule::in([Lead::SOURCE_CALLBACK, Lead::SOURCE_POPUP])],
            'page' => ['nullable', 'string', 'max:255'],
            'consent_terms' => ['accepted'],
            'consent_privacy' => ['accepted'],
            /* Реклама по желанию: без галочки заявка тоже принимается (16.10) */
            'consent_ads' => ['sometimes', 'boolean'],
        ], [
            'phone.regex' => 'В телефоне могут быть только цифры, пробелы, скобки, плюс и дефис.',
        ], [
            'name' => '«имя»',
            'phone' => '«телефон»',
            'interest' => '«что вас интересует»',
            'consent_terms' => '«согласие с соглашением и политикой»',
            'consent_privacy' => '«согласие на обработку персональных данных»',
            'consent_ads' => '«согласие на рекламу»',
        ]);

        $lead = Lead::create([
            ...$data,
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'interest' => isset($data['interest']) ? trim($data['interest']) ?: null : null,
            'consent_terms' => true,
            'consent_privacy' => true,
            'consent_ads' => (bool) ($data['consent_ads'] ?? false),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'status' => Lead::STATUS_NEW,
        ]);

        $this->notifier->notify($lead);

        return response()->json(['data' => ['id' => $lead->id]], 201);
    }

    /*
     * Число цифр как в номерах страны (по коду после «+», без кода номер
     * российский), 16.10. Существует ли номер, не проверяется. Правила стран из
     * libphonenumber (giggsey/libphonenumber-for-php-lite), как и на сайте.
     */
    private function phoneLength(string $attribute, mixed $value, Closure $fail): void
    {
        $util = PhoneNumberUtil::getInstance();
        try {
            $ok = $util->isPossibleNumber($util->parse((string) $value, 'RU'));
        } catch (NumberParseException) {
            $ok = false;
        }
        if (! $ok) {
            $fail('Проверьте номер телефона: не хватает цифр или есть лишние. Например, +7 999 123-45-67 или +381 64 123 45 67.');
        }
    }
}
