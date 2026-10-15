<?php

namespace App\Http\Resources;

use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Photo */
class PhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $size = $this->size();

        return [
            'url' => $this->url(),
            'alt' => $this->alt ?? '',
            'width' => $size[0] ?? null,
            'height' => $size[1] ?? null,
        ];
    }
}
