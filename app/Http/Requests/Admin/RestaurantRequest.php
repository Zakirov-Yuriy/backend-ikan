<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/* Форма страницы «Ресторан»: тексты, таблица, характеристики, фотографии */
class RestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $image = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'];

        return [
            'title' => ['required', 'string', 'max:150'],
            'lead' => ['nullable', 'string', 'max:1000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'price_text' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'info_type' => ['nullable', 'string', 'max:150'],
            'info_purpose' => ['nullable', 'string', 'max:150'],
            'info_price' => ['nullable', 'string', 'max:100'],
            'info_floor' => ['nullable', 'string', 'max:100'],
            'info_note' => ['nullable', 'string', 'max:1000'],
            'about_title' => ['nullable', 'string', 'max:255'],
            'about_text' => ['nullable', 'string', 'max:5000'],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*.label' => ['nullable', 'string', 'max:100'],
            'features.*.value' => ['nullable', 'string', 'max:255'],
            'data_items' => ['nullable', 'array', 'max:20'],
            'data_items.*.label' => ['nullable', 'string', 'max:100'],
            'data_items.*.value' => ['nullable', 'string', 'max:255'],
            'consultation_title' => ['nullable', 'string', 'max:255'],

            'photo_mobile' => $image,
            'gallery' => ['nullable', 'array', 'max:30'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'delete_photo' => ['nullable', 'array'],
            'delete_photo.*' => ['integer'],
            'photo_order' => ['nullable', 'array'],
            'photo_order.*' => ['integer'],
            'photo_alt' => ['nullable', 'array'],
            'photo_alt.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => '«заголовок страницы»',
            'lead' => '«подзаголовок»',
            'meta_title' => '«заголовок вкладки»',
            'meta_description' => '«описание для поиска»',
            'status' => '«статус»',
            'name' => '«название объекта»',
            'city' => '«город»',
            'floor' => '«этаж»',
            'type' => '«тип»',
            'area' => '«площадь»',
            'price_text' => '«стоимость»',
            'description' => '«описание»',
            'info_type' => '«тип в таблице»',
            'info_purpose' => '«назначение»',
            'info_price' => '«стоимость в таблице»',
            'info_floor' => '«этаж в таблице»',
            'info_note' => '«примечание»',
            'about_title' => '«заголовок блока «Об объекте»»',
            'about_text' => '«текст блока «Об объекте»»',
            'features' => '«характеристики»',
            'data_items' => '«данные»',
            'consultation_title' => '«заголовок блока подбора»',
            'photo_mobile' => '«фото для телефона»',
            'gallery' => '«галерея»',
            'gallery.*' => '«файл галереи»',
        ];
    }

    /* Поля таблицы restaurants из формы: пары подпись и значение без пустых строк */
    public function restaurantData(): array
    {
        $data = $this->safe()->except([
            'features', 'data_items', 'photo_mobile', 'gallery', 'delete_photo', 'photo_order', 'photo_alt',
        ]);
        foreach (['features', 'data_items'] as $key) {
            $rows = [];
            foreach ((array) $this->input($key, []) as $item) {
                $label = trim((string) ($item['label'] ?? ''));
                $value = trim((string) ($item['value'] ?? ''));
                if ($label !== '' || $value !== '') {
                    $rows[] = ['label' => $label, 'value' => $value];
                }
            }
            $data[$key] = $rows;
        }
        if (array_key_exists('area', $data) && ($data['area'] === '' || $data['area'] === null)) {
            $data['area'] = null;
        }

        return $data;
    }
}
