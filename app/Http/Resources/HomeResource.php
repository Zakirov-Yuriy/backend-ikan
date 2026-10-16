<?php

namespace App\Http\Resources;

use App\Models\Apartment;
use App\Models\HomePage;
use App\Models\HomeRow;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Главная страница сайта целиком: тексты блоков, строки направлений,
 * карточки «Наши направления», карточки «Актуальных объектов».
 *
 * @mixin HomePage
 */
class HomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /* Списки строк отдаются массивами: тогда пустые фотографии становятся null, а не {} */
        $rows = HomeRow::query()->with('photos')->ordered()->get();
        $objects = Apartment::homeObjects();

        return [
            'meta' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
            ],
            'hero' => [
                'eyebrow' => $this->hero_eyebrow,
                'title' => $this->hero_title,
                'title_accent' => $this->hero_title_accent,
                'lead' => $this->hero_lead,
                'primary_label' => $this->hero_primary_label,
                'secondary_label' => $this->hero_secondary_label,
                'side_label' => $this->hero_side_label,
                'side_items' => array_values($this->hero_side_items ?? []),
            ],
            'scenarios' => $rows->where('kind', HomeRow::KIND_SCENARIO)->values()->map(fn (HomeRow $row) => [
                'title' => $row->title,
                'text' => $row->text,
                'link' => ['label' => $row->link_label, 'href' => $row->link_href],
                'photo' => PhotoResource::make($row->photo(Photo::ROLE_PHOTO)),
                'photo_wide' => PhotoResource::make($row->photo(Photo::ROLE_PHOTO_WIDE)),
            ])->all(),
            'about' => [
                'eyebrow' => $this->about_eyebrow,
                'title' => $this->about_title,
                'text' => $this->about_text,
                'features_label' => $this->about_features_label,
                'features' => array_values($this->about_features ?? []),
            ],
            'directions' => [
                'label' => $this->directions_label,
                'items' => $rows->where('kind', HomeRow::KIND_DIRECTION)->values()->map(fn (HomeRow $row) => [
                    'title' => $row->title,
                    'text' => $row->text,
                    'href' => $row->link_href,
                    'photo' => PhotoResource::make($row->photo(Photo::ROLE_PHOTO)),
                ])->all(),
            ],
            'objects' => [
                'eyebrow' => $this->objects_eyebrow,
                'title' => $this->objects_title,
                'all' => ['label' => $this->objects_all_label, 'href' => $this->objects_all_href],
                'more' => $this->objects_more_label,
                'items' => ApartmentCardResource::collection($objects),
            ],
            'consultation' => [
                'eyebrow' => $this->consultation_eyebrow,
                'title' => $this->consultation_title,
                'text' => $this->consultation_text,
                'button_label' => $this->consultation_button_label,
                'photo' => PhotoResource::make($this->photo(Photo::ROLE_CONSULTATION)),
            ],
        ];
    }
}
