<?php

namespace App\Http\Resources;

use App\Models\Photo;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Restaurant */
class RestaurantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'lead' => $this->lead,
            'meta' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
            ],
            'status' => $this->status,
            'name' => $this->name,
            'city' => $this->city,
            'floor' => $this->floor,
            'type' => $this->type,
            'area' => $this->area === null ? null : (float) $this->area,
            'price_text' => $this->price_text,
            'description' => $this->description,
            'info' => [
                'type' => $this->info_type,
                'purpose' => $this->info_purpose,
                'price' => $this->info_price,
                'floor' => $this->info_floor,
                'note' => $this->info_note,
            ],
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
