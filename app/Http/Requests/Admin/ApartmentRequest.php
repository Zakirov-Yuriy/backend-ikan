<?php

namespace App\Http\Requests\Admin;

use App\Enums\ApartmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/* Форма апартамента: все поля карточки и страницы плюс фотографии */
class ApartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $slug = trim((string) $this->input('slug'));
        $this->merge([
            'slug' => Str::slug($slug !== '' ? $slug : (string) $this->input('name')),
            'show_living' => $this->boolean('show_living'),
            'show_renting' => $this->boolean('show_renting'),
            'show_newbuilds' => $this->boolean('show_newbuilds'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('apartment')?->id;
        $image = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'];

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash:ascii', Rule::unique('apartments', 'slug')->ignore($id)],
            'complex_id' => ['required', 'integer', 'exists:complexes,id'],
            'status' => ['required', Rule::enum(ApartmentStatus::class)],
            'show_living' => ['boolean'],
            'show_renting' => ['boolean'],
            'show_newbuilds' => ['boolean'],
            'city' => ['nullable', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'card_text' => ['nullable', 'string', 'max:1000'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'description_short' => ['nullable', 'string', 'max:1000'],
            'info_note' => ['nullable', 'string', 'max:1000'],
            'about_title' => ['nullable', 'string', 'max:255'],
            'about_text' => ['nullable', 'string', 'max:5000'],
            'features_text' => ['nullable', 'string', 'max:5000'],
            'data_items' => ['nullable', 'array', 'max:20'],
            'data_items.*.label' => ['nullable', 'string', 'max:100'],
            'data_items.*.value' => ['nullable', 'string', 'max:255'],
            'consultation_title' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],

            'photo_card' => $image,
            'photo_card_mobile' => $image,
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
            'name' => '«название»',
            'slug' => '«адрес страницы»',
            'complex_id' => '«жилой комплекс»',
            'status' => '«стадия»',
            'city' => '«город»',
            'floor' => '«этаж»',
            'type' => '«тип»',
            'area' => '«площадь»',
            'price' => '«цена»',
            'card_text' => '«текст карточки»',
            'meta_title' => '«заголовок вкладки»',
            'meta_description' => '«описание для поиска»',
            'description' => '«описание»',
            'description_short' => '«описание для телефона»',
            'info_note' => '«примечание»',
            'about_title' => '«заголовок блока «Об объекте»»',
            'about_text' => '«текст блока «Об объекте»»',
            'features_text' => '«характеристики»',
            'data_items' => '«данные»',
            'consultation_title' => '«заголовок блока подбора»',
            'sort_order' => '«порядок»',
            'photo_card' => '«фото карточки»',
            'photo_card_mobile' => '«фото карточки для телефона»',
            'photo_mobile' => '«фото страницы для телефона»',
            'gallery' => '«галерея»',
            'gallery.*' => '«файл галереи»',
        ];
    }

    /* Поля таблицы apartments из формы: характеристики из строк, данные из пар */
    public function apartmentData(): array
    {
        $data = $this->safe()->except([
            'features_text', 'data_items', 'photo_card', 'photo_card_mobile', 'photo_mobile',
            'gallery', 'delete_photo', 'photo_order', 'photo_alt',
        ]);

        $features = preg_split('/\r\n|\r|\n/', (string) $this->input('features_text')) ?: [];
        $data['features'] = array_values(array_filter(array_map('trim', $features), fn ($line) => $line !== ''));

        $items = [];
        foreach ((array) $this->input('data_items', []) as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            $value = trim((string) ($item['value'] ?? ''));
            if ($label !== '' || $value !== '') {
                $items[] = ['label' => $label, 'value' => $value];
            }
        }
        $data['data_items'] = $items;
        if (array_key_exists('sort_order', $data)) {
            $data['sort_order'] = (int) $data['sort_order'];
        }
        /* Пустые числа из формы это «нет значения», а не ноль */
        foreach (['area', 'price'] as $key) {
            if (array_key_exists($key, $data) && ($data[$key] === '' || $data[$key] === null)) {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
