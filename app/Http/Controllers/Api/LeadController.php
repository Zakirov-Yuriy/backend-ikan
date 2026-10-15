<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'phone' => ['required', 'string', 'min:5', 'max:50', 'regex:/^[\d\s()+\-]+$/'],
            'interest' => ['nullable', 'string', 'max:2000'],
            'source' => ['required', Rule::in([Lead::SOURCE_CALLBACK, Lead::SOURCE_POPUP])],
            'page' => ['nullable', 'string', 'max:255'],
            'consent_terms' => ['accepted'],
            'consent_privacy' => ['accepted'],
        ], [
            'phone.regex' => 'В телефоне могут быть только цифры, пробелы, скобки, плюс и дефис.',
        ], [
            'name' => '«имя»',
            'phone' => '«телефон»',
            'interest' => '«что вас интересует»',
            'consent_terms' => '«согласие с условиями»',
            'consent_privacy' => '«согласие с политикой»',
        ]);

        $lead = Lead::create([
            ...$data,
            'name' => trim($data['name']),
            'phone' => trim($data['phone']),
            'interest' => isset($data['interest']) ? trim($data['interest']) ?: null : null,
            'consent_terms' => true,
            'consent_privacy' => true,
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            'status' => Lead::STATUS_NEW,
        ]);

        $this->notifier->notify($lead);

        return response()->json(['data' => ['id' => $lead->id]], 201);
    }
}
