<?php

namespace App\Http\Resources;

use App\Models\Apartment;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Карточка апартамента для списков сайта.
 *
 * @mixin Apartment
 */
class ApartmentCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'url' => $this->siteUrl(),
            'complex' => new ComplexResource($this->complex),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'city' => $this->city,
            'floor' => $this->floor,
            'type' => $this->type,
            'area' => $this->area === null ? null : (float) $this->area,
            'price' => $this->price,
            'card_text' => $this->card_text,
            'catalogs' => [
                'living' => $this->show_living,
                'renting' => $this->show_renting,
                'newbuilds' => $this->show_newbuilds,
                'home' => $this->show_home,
            ],
            'photos' => [
                'card' => PhotoResource::make($this->photo(Photo::ROLE_CARD)),
                'card_mobile' => PhotoResource::make($this->photo(Photo::ROLE_CARD_MOBILE)),
            ],
        ];
    }
}
