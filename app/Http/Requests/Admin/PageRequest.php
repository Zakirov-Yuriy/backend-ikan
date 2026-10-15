<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/* Вступление страницы списка */
class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'lead' => ['nullable', 'string', 'max:1000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'consultation_title' => ['nullable', 'string', 'max:255'],
            'consultation_title_mobile' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => '«заголовок»',
            'lead' => '«подзаголовок»',
            'meta_title' => '«заголовок вкладки»',
            'meta_description' => '«описание для поиска»',
            'consultation_title' => '«заголовок блока подбора»',
            'consultation_title_mobile' => '«заголовок блока подбора на телефоне»',
        ];
    }
}
