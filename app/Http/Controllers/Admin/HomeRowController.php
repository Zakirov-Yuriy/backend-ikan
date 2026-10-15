<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HomeRowRequest;
use App\Models\HomeRow;
use App\Models\Photo;
use App\Services\PhotoStore;
use App\Support\SitePages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/* Строки направлений и карточки «Наши направления» на главной */
class HomeRowController extends Controller
{
    public function __construct(private readonly PhotoStore $photos) {}

    public function create(Request $request): View
    {
        $kind = $request->validate(['kind' => ['required', Rule::in(array_keys(HomeRow::KINDS))]])['kind'];

        return $this->form(new HomeRow(['kind' => $kind]));
    }

    public function store(HomeRowRequest $request): RedirectResponse
    {
        $row = new HomeRow($request->rowData());
        $row->kind = $request->input('kind');
        $row->sort_order = (int) HomeRow::query()->ofKind($row->kind)->max('sort_order') + 1;
        $row->save();
        $this->savePhotos($request, $row);

        return $this->back($row, 'добавлена');
    }

    public function edit(HomeRow $homeRow): View
    {
        return $this->form($homeRow);
    }

    public function update(HomeRowRequest $request, HomeRow $homeRow): RedirectResponse
    {
        $homeRow->update($request->rowData());
        $this->savePhotos($request, $homeRow);

        return $this->back($homeRow, 'сохранена');
    }

    public function destroy(HomeRow $homeRow): RedirectResponse
    {
        $this->photos->deleteAll($homeRow);
        $homeRow->delete();

        return $this->back($homeRow, 'удалена');
    }

    /* Новый порядок строк одного вида: список id в нужном порядке */
    public function sort(Request $request): RedirectResponse
    {
        $ids = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']])['order'];
        foreach (array_values($ids) as $i => $id) {
            HomeRow::query()->whereKey($id)->update(['sort_order' => $i + 1]);
        }

        return back()->with('success', 'Порядок на главной сохранён.');
    }

    private function form(HomeRow $row): View
    {
        return view('admin.home-rows.form', [
            'row' => $row,
            'linkGroups' => SitePages::groups(),
        ]);
    }

    private function savePhotos(HomeRowRequest $request, HomeRow $row): void
    {
        $row->load('photos');

        foreach ((array) $request->input('delete_photo', []) as $id) {
            $photo = $row->photos->firstWhere('id', (int) $id);
            /* Основную фотографию убрать нельзя, только заменить; широкий кадр можно */
            if ($photo && $photo->role === Photo::ROLE_PHOTO_WIDE) {
                $this->photos->delete($photo);
            }
        }
        foreach ((array) $request->input('photo_alt', []) as $id => $alt) {
            $photo = $row->photos->firstWhere('id', (int) $id);
            if ($photo && $photo->exists && $photo->alt !== ($alt = trim((string) $alt) ?: null)) {
                $photo->update(['alt' => $alt]);
            }
        }
        foreach ([Photo::ROLE_PHOTO, Photo::ROLE_PHOTO_WIDE] as $role) {
            if ($request->hasFile($role)) {
                $this->photos->replace($row, $role, $request->file($role), $request->input('alt_'.$role));
            }
        }
    }

    private function back(HomeRow $row, string $done): RedirectResponse
    {
        $what = $row->isScenario() ? 'Строка' : 'Карточка';
        $anchor = $row->isScenario() ? '#scenarios' : '#directions';

        return redirect(route('admin.home-page.edit').$anchor)->with('success', "{$what} «{$row->title}» {$done}.");
    }
}
