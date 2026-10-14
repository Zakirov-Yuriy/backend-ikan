<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApartmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApartmentRequest;
use App\Models\Apartment;
use App\Models\Complex;
use App\Models\Photo;
use App\Services\PhotoStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/* Апартаменты: список с отбором по ЖК и поиском, форма с фотографиями */
class ApartmentController extends Controller
{
    public function __construct(private readonly PhotoStore $photos) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'complex' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Apartment::query()->with(['complex', 'photos'])->ordered();
        if (! empty($filters['complex'])) {
            $query->where('complex_id', $filters['complex']);
        }
        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return view('admin.apartments.index', [
            'apartments' => $query->paginate(20)->withQueryString(),
            'complexes' => Complex::query()->orderBy('sort_order')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $apartment = new Apartment([
            'complex_id' => $request->integer('complex') ?: null,
            'status' => ApartmentStatus::Building,
            'show_living' => true,
            'show_renting' => true,
            'show_newbuilds' => true,
            'city' => 'Златибор',
            'consultation_title' => 'Получить консультацию по объекту',
        ]);

        return $this->form($apartment);
    }

    public function store(ApartmentRequest $request): RedirectResponse
    {
        $apartment = new Apartment($request->apartmentData());
        if ((int) $apartment->sort_order === 0) {
            $apartment->sort_order = (int) Apartment::query()->max('sort_order') + 1;
        }
        $apartment->save();
        $this->savePhotos($request, $apartment);

        return redirect()->route('admin.apartments.edit', $apartment)->with('success', "Апартамент «{$apartment->name}» добавлен.");
    }

    public function edit(Apartment $apartment): View
    {
        return $this->form($apartment);
    }

    public function update(ApartmentRequest $request, Apartment $apartment): RedirectResponse
    {
        $oldSlug = $apartment->slug;
        $apartment->fill($request->apartmentData())->save();
        if ($oldSlug !== $apartment->slug) {
            $this->movePhotos($apartment, $oldSlug);
        }
        $this->savePhotos($request, $apartment);

        return redirect()->route('admin.apartments.edit', $apartment)->with('success', "Апартамент «{$apartment->name}» сохранён.");
    }

    public function destroy(Apartment $apartment): RedirectResponse
    {
        $this->photos->deleteAll($apartment);
        $apartment->delete();

        return redirect()->route('admin.apartments.index')->with('success', "Апартамент «{$apartment->name}» удалён.");
    }

    /* Новый порядок карточек: список id в нужном порядке */
    public function sort(Request $request): RedirectResponse
    {
        $ids = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']])['order'];
        foreach (array_values($ids) as $i => $id) {
            Apartment::query()->whereKey($id)->update(['sort_order' => $i + 1]);
        }

        return back()->with('success', 'Порядок апартаментов сохранён.');
    }

    private function form(Apartment $apartment): View
    {
        return view('admin.apartments.form', [
            'apartment' => $apartment,
            'complexes' => Complex::query()->orderBy('sort_order')->get(),
            'statuses' => ApartmentStatus::options(),
        ]);
    }

    /* Фотографии из формы: удаление, подписи, порядок галереи, новые файлы */
    private function savePhotos(ApartmentRequest $request, Apartment $apartment): void
    {
        $apartment->load('photos');

        foreach ((array) $request->input('delete_photo', []) as $id) {
            $photo = $apartment->photos->firstWhere('id', (int) $id);
            if ($photo) {
                $this->photos->delete($photo);
            }
        }

        foreach ((array) $request->input('photo_alt', []) as $id => $alt) {
            $photo = $apartment->photos->firstWhere('id', (int) $id);
            if ($photo && $photo->exists && $photo->alt !== ($alt = trim((string) $alt) ?: null)) {
                $photo->update(['alt' => $alt]);
            }
        }

        foreach (array_values((array) $request->input('photo_order', [])) as $i => $id) {
            $photo = $apartment->photos->firstWhere('id', (int) $id);
            if ($photo && $photo->exists && $photo->role === Photo::ROLE_GALLERY) {
                $photo->update(['sort_order' => $i + 1]);
            }
        }

        foreach ([
            'photo_card' => Photo::ROLE_CARD,
            'photo_card_mobile' => Photo::ROLE_CARD_MOBILE,
            'photo_mobile' => Photo::ROLE_MOBILE,
        ] as $field => $role) {
            if ($request->hasFile($field)) {
                $this->photos->replace($apartment, $role, $request->file($field));
            }
        }

        foreach ((array) $request->file('gallery', []) as $file) {
            $this->photos->append($apartment, $file);
        }
    }

    /* Адрес страницы сменился: папка с файлами переезжает вслед за ним */
    private function movePhotos(Apartment $apartment, string $oldSlug): void
    {
        $disk = Storage::disk('media');
        $from = 'apartments/'.$oldSlug;
        $to = 'apartments/'.$apartment->slug;
        foreach ($apartment->photos as $photo) {
            if (str_starts_with($photo->path, $from.'/')) {
                $newPath = $to.substr($photo->path, strlen($from));
                if ($disk->move($photo->path, $newPath)) {
                    $photo->update(['path' => $newPath]);
                }
            }
        }
        $disk->deleteDirectory($from);
    }
}
