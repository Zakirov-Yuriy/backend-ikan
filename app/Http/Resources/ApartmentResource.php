<?php

namespace App\Http\Resources;

use App\Models\Apartment;
use App\Models\Photo;
use Illuminate\Http\Request;

/**
 * Страница апартамента: карточка плюс всё, что есть на странице.
 *
 * @mixin Apartment
 */
class ApartmentResource extends ApartmentCardResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'meta' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
            ],
            'description' => $this->description,
            'description_short' => $this->description_short,
            'info_note' => $this->info_note,
            'about' => [
                'title' => $this->about_title,
                'text' => $this->about_text,
                'features' => $this->features ?? [],
            ],
            'data_items' => $this->data_items ?? [],
            'consultation_title' => $this->consultation_title,
            'gallery' => PhotoResource::collection(
                $this->photos->where('role', Photo::ROLE_GALLERY)->values()
            ),
            'photo_mobile' => PhotoResource::make($this->photo(Photo::ROLE_MOBILE)),
        ];
    }
}
