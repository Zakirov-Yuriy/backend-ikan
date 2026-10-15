<?php

namespace App\Http\Requests\Admin;

use App\Models\HomeRow;
use App\Support\SitePages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/* Строка направления или карточка «Наши направления» на главной */
class HomeRowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $row = $this->route('home_row');
        $kind = $row?->kind ?? (string) $this->input('kind');
        $image = ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'];

        return [
            'kind' => [$row ? 'nullable' : 'required', Rule::in(array_keys(HomeRow::KINDS))],
            'title' => ['required', 'string', 'max:255'],
            'text' => ['nullable', 'string', 'max:1000'],
            'link_label' => [$kind === HomeRow::KIND_SCENARIO ? 'required' : 'nullable', 'string', 'max:150'],
            'link_href' => ['required', 'string', 'max:255', SitePages::rule()],
            /* Фотография обязательна у новой строки; у существующей новый файл заменяет старый */
            'photo' => [$row ? 'nullable' : 'required', ...$image],
            'photo_wide' => ['nullable', ...$image],
            'delete_photo' => ['nullable', 'array'],
            'delete_photo.*' => ['integer'],
            'photo_alt' => ['nullable', 'array'],
            'photo_alt.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'kind' => '«вид строки»',
            'title' => '«заголовок»',
            'text' => '«текст»',
            'link_label' => '«текст ссылки»',
            'link_href' => '«адрес ссылки»',
            'photo' => '«фотография»',
            'photo_wide' => '«фото для компьютера»',
        ];
    }

    public function messages(): array
    {
        return [
            'link_href.regex' => 'Адрес ссылки должен начинаться с «/», «#» или «http».',
        ];
    }

    public function rowData(): array
    {
        return $this->safe()->only(['title', 'text', 'link_label', 'link_href']);
    }
}
