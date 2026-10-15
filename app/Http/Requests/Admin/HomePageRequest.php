<?php

namespace App\Http\Requests\Admin;

use App\Models\HomePage;
use App\Support\SitePages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/* Тексты главной страницы сайта */
class HomePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],

            'hero_eyebrow' => ['nullable', 'string', 'max:255'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_title_accent' => ['nullable', 'string', 'max:255'],
            'hero_lead' => ['nullable', 'string', 'max:1000'],
            'hero_primary_label' => ['required', 'string', 'max:100'],
            'hero_secondary_label' => ['required', 'string', 'max:100'],
            'hero_side_label' => ['nullable', 'string', 'max:255'],
            'hero_side_items_text' => ['nullable', 'string', 'max:1000'],

            'about_eyebrow' => ['nullable', 'string', 'max:255'],
            'about_title' => ['nullable', 'string', 'max:255'],
            'about_text' => ['nullable', 'string', 'max:2000'],
            'about_features_label' => ['nullable', 'string', 'max:255'],
            'about_features' => ['nullable', 'array', 'max:12'],
            'about_features.*.icon' => ['nullable', Rule::in(array_keys(HomePage::ICONS))],
            'about_features.*.label' => ['nullable', 'string', 'max:100'],

            'directions_label' => ['nullable', 'string', 'max:255'],

            'objects_eyebrow' => ['nullable', 'string', 'max:255'],
            'objects_title' => ['nullable', 'string', 'max:255'],
            'objects_all_label' => ['nullable', 'string', 'max:100'],
            'objects_all_href' => ['nullable', 'string', 'max:255', SitePages::rule()],
            'objects_more_label' => ['nullable', 'string', 'max:100'],

            'consultation_eyebrow' => ['nullable', 'string', 'max:255'],
            'consultation_title' => ['nullable', 'string', 'max:255'],
            'consultation_text' => ['nullable', 'string', 'max:1000'],
            'consultation_button_label' => ['nullable', 'string', 'max:100'],

            'consultation_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'delete_photo' => ['nullable', 'array'],
            'delete_photo.*' => ['integer'],
            'photo_alt' => ['nullable', 'array'],
            'photo_alt.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'meta_title' => '«заголовок вкладки»',
            'meta_description' => '«описание для поиска»',
            'hero_eyebrow' => '«надпись над заголовком»',
            'hero_title' => '«заголовок»',
            'hero_title_accent' => '«вторая строка заголовка»',
            'hero_lead' => '«текст под заголовком»',
            'hero_primary_label' => '«тёмная кнопка»',
            'hero_secondary_label' => '«светлая кнопка»',
            'hero_side_label' => '«подпись списка справа»',
            'hero_side_items_text' => '«список справа»',
            'about_eyebrow' => '«надпись над заголовком «О компании»»',
            'about_title' => '«заголовок «О компании»»',
            'about_text' => '«текст «О компании»»',
            'about_features_label' => '«подпись над преимуществами»',
            'about_features' => '«преимущества»',
            'about_features.*.icon' => '«значок»',
            'about_features.*.label' => '«преимущество»',
            'directions_label' => '«подпись «Наши направления»»',
            'objects_eyebrow' => '«надпись над «Актуальными объектами»»',
            'objects_title' => '«заголовок «Актуальные объекты»»',
            'objects_all_label' => '«ссылка «Весь каталог»»',
            'objects_all_href' => '«адрес «Весь каталог»»',
            'objects_more_label' => '«ссылка в карточке»',
            'consultation_eyebrow' => '«надпись над блоком подбора»',
            'consultation_title' => '«заголовок блока подбора»',
            'consultation_text' => '«текст блока подбора»',
            'consultation_button_label' => '«кнопка блока подбора»',
            'consultation_photo' => '«фото блока подбора»',
        ];
    }

    public function messages(): array
    {
        return [
            'objects_all_href.regex' => 'Адрес «Весь каталог» должен начинаться с «/», «#» или «http».',
        ];
    }

    /* Поля таблицы home_pages из формы: список справа по строкам, преимущества без пустых */
    public function homeData(): array
    {
        $data = $this->safe()->except([
            'hero_side_items_text', 'about_features', 'consultation_photo', 'delete_photo', 'photo_alt',
        ]);

        $items = preg_split('/\R/u', (string) $this->input('hero_side_items_text', '')) ?: [];
        $data['hero_side_items'] = array_values(array_filter(array_map('trim', $items), fn ($item) => $item !== ''));

        $features = [];
        foreach ((array) $this->input('about_features', []) as $item) {
            $label = trim((string) ($item['label'] ?? ''));
            if ($label !== '') {
                $icon = (string) ($item['icon'] ?? '');
                $features[] = ['icon' => isset(HomePage::ICONS[$icon]) ? $icon : 'chart', 'label' => $label];
            }
        }
        $data['about_features'] = $features;

        return $data;
    }
}
