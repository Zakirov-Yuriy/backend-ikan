<?php

namespace App\Http\Resources;

use App\Models\HomePage;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Вступление страницы списка.
 *
 * @mixin Page
 */
class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'url' => $this->siteUrl(),
            'title' => $this->title,
            'lead' => $this->lead,
            'meta' => [
                'title' => $this->meta_title,
                'description' => $this->meta_description,
            ],
            /* Пустой заголовок блока подбора: как на главной, из её текстов */
            'consultation' => [
                'title' => $this->consultation_title ?? HomePage::current()?->consultation_title,
                'title_mobile' => $this->consultation_title_mobile,
            ],
        ];
    }
}
