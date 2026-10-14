<?php

namespace App\Http\Controllers\Api;

use App\Enums\ApartmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApartmentCardResource;
use App\Http\Resources\ApartmentResource;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ApartmentController extends Controller
{
    /*
     * Карточки для списков сайта.
     * ?catalog=living|renting|newbuilds отбирает по каталогу,
     * ?status=building|ready по стадии, ?complex=l1 по ЖК.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'catalog' => ['sometimes', Rule::in(array_keys(Apartment::CATALOGS))],
            'status' => ['sometimes', Rule::enum(ApartmentStatus::class)],
            'complex' => ['sometimes', 'string', 'exists:complexes,slug'],
        ]);

        $query = Apartment::query()->with(['complex', 'photos'])->ordered();

        if (isset($filters['catalog'])) {
            $query->inCatalog($filters['catalog']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['complex'])) {
            $query->whereRelation('complex', 'slug', $filters['complex']);
        }

        return ApartmentCardResource::collection($query->get());
    }

    /* Страница апартамента по slug: /api/apartments/a4 */
    public function show(string $slug): ApartmentResource
    {
        $apartment = Apartment::query()
            ->with(['complex', 'photos'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ApartmentResource($apartment);
    }
}
