<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomePageRequest;
use App\Models\Apartment;
use App\Models\HomePage;
use App\Models\HomeRow;
use App\Models\Photo;
use App\Services\PhotoStore;
use App\Support\SitePages;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/* «Главная сайта»: тексты блоков, строки направлений, карточки, актуальные объекты */
class HomePageController extends Controller
{
    public function __construct(private readonly PhotoStore $photos) {}

    public function edit(): View
    {
        $rows = HomeRow::query()->with('photos')->ordered()->get();

        return view('admin.home-page.form', [
            'home' => HomePage::current() ?? new HomePage,
            'scenarios' => $rows->where('kind', HomeRow::KIND_SCENARIO)->values(),
            'directions' => $rows->where('kind', HomeRow::KIND_DIRECTION)->values(),
            'objects' => Apartment::query()->with('complex')->onHome()->ordered()->get(),
            'linkGroups' => SitePages::groups(),
        ]);
    }

    public function update(HomePageRequest $request): RedirectResponse
    {
        $home = HomePage::current() ?? new HomePage;
        $home->fill($request->homeData())->save();
        $home->load('photos');

        foreach ((array) $request->input('delete_photo', []) as $id) {
            $photo = $home->photos->firstWhere('id', (int) $id);
            if ($photo) {
                $this->photos->delete($photo);
            }
        }
        foreach ((array) $request->input('photo_alt', []) as $id => $alt) {
            $photo = $home->photos->firstWhere('id', (int) $id);
            if ($photo && $photo->exists && $photo->alt !== ($alt = trim((string) $alt) ?: null)) {
                $photo->update(['alt' => $alt]);
            }
        }
        if ($request->hasFile('consultation_photo')) {
            $this->photos->replace($home, Photo::ROLE_CONSULTATION, $request->file('consultation_photo'));
        }

        return redirect()->route('admin.home-page.edit')->with('success', 'Тексты главной сохранены.');
    }
}
