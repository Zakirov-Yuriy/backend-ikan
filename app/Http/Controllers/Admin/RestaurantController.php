<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RestaurantRequest;
use App\Models\Photo;
use App\Models\Restaurant;
use App\Services\PhotoStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/* Страница «Ресторан»: одна запись, только правка */
class RestaurantController extends Controller
{
    public function __construct(private readonly PhotoStore $photos) {}

    public function edit(): View
    {
        return view('admin.restaurant.form', [
            'restaurant' => Restaurant::current() ?? new Restaurant,
        ]);
    }

    public function update(RestaurantRequest $request): RedirectResponse
    {
        $restaurant = Restaurant::current() ?? new Restaurant;
        $restaurant->fill($request->restaurantData())->save();
        $this->savePhotos($request, $restaurant);

        return redirect()->route('admin.restaurant.edit')->with('success', 'Страница «Ресторан» сохранена.');
    }

    /* Фотографии: удаление, подписи, порядок галереи, новые файлы */
    private function savePhotos(RestaurantRequest $request, Restaurant $restaurant): void
    {
        $restaurant->load('photos');

        foreach ((array) $request->input('delete_photo', []) as $id) {
            $photo = $restaurant->photos->firstWhere('id', (int) $id);
            if ($photo) {
                $this->photos->delete($photo);
            }
        }

        foreach ((array) $request->input('photo_alt', []) as $id => $alt) {
            $photo = $restaurant->photos->firstWhere('id', (int) $id);
            if ($photo && $photo->exists && $photo->alt !== ($alt = trim((string) $alt) ?: null)) {
                $photo->update(['alt' => $alt]);
            }
        }

        foreach (array_values((array) $request->input('photo_order', [])) as $i => $id) {
            $photo = $restaurant->photos->firstWhere('id', (int) $id);
            if ($photo && $photo->exists && $photo->role === Photo::ROLE_GALLERY) {
                $photo->update(['sort_order' => $i + 1]);
            }
        }

        if ($request->hasFile('photo_mobile')) {
            $this->photos->replace($restaurant, Photo::ROLE_MOBILE, $request->file('photo_mobile'));
        }

        foreach ((array) $request->file('gallery', []) as $file) {
            $this->photos->append($restaurant, $file);
        }
    }
}
