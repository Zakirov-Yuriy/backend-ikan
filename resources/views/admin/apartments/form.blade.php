@extends('admin.layout')
@section('title', $apartment->exists ? 'Изменить апартамент' : 'Новый апартамент')
@push('css')
    <link rel="stylesheet" href="{{ asset('admin_front/assets/css/jquery-ui.min.css') }}"/>
@endpush
@push('script')
    <script src="{{ asset('admin_front/assets/js/jquery-ui.min.js') }}"></script>
@endpush
@php
    use App\Models\Photo;
    $v = fn (string $field) => old($field, $apartment->$field);
    $single = [
        'photo_card' => ['role' => Photo::ROLE_CARD, 'label' => 'Фото карточки (компьютер)', 'hint' => 'В списках на компьютере, кадр примерно 4:3'],
        'photo_card_mobile' => ['role' => Photo::ROLE_CARD_MOBILE, 'label' => 'Фото карточки (телефон)', 'hint' => 'В списках на телефоне, кадр примерно 3:2'],
        'photo_mobile' => ['role' => Photo::ROLE_MOBILE, 'label' => 'Фото страницы (телефон)', 'hint' => 'Вверху страницы апартамента на телефоне'],
    ];
    $gallery = $apartment->exists ? $apartment->photos->where('role', Photo::ROLE_GALLERY)->values() : collect();
    $items = old('data_items', $apartment->data_items ?? []);
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <form action="{{ $apartment->exists ? route('admin.apartments.update', $apartment) : route('admin.apartments.store') }}" method="POST" enctype="multipart/form-data" id="apartmentForm">
            @csrf
            @if($apartment->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-xl-8">
                    <div class="card mb-4">
                        <h5 class="card-header">{{ $apartment->exists ? $apartment->name : 'Новый апартамент' }}</h5>
                        <div class="card-body">
                            @include('admin.notifications')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Название*</label>
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Апартамент A4" required maxlength="150" value="{{ $v('name') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="slug" class="form-label">Адрес страницы (латиницей)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">/apartamenty/</span>
                                        <input type="text" class="form-control" name="slug" id="slug" placeholder="a4" maxlength="100" value="{{ $v('slug') }}"/>
                                    </div>
                                    <div class="form-text">Пустое заполнится из названия.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="complex_id" class="form-label">Жилой комплекс*</label>
                                    <select class="form-select" name="complex_id" id="complex_id" required>
                                        <option value="">Выберите ЖК</option>
                                        @foreach($complexes as $complex)
                                            <option value="{{ $complex->id }}" @selected((string) $v('complex_id') === (string) $complex->id)>{{ $complex->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Стадия*</label>
                                    <select class="form-select" name="status" id="status" required>
                                        @foreach($statuses as $value => $label)
                                            <option value="{{ $value }}" @selected(($v('status') instanceof \App\Enums\ApartmentStatus ? $v('status')->value : $v('status')) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">По стадии карточка попадает во вкладки «Строится» и «Готово» на странице новостроек.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label d-block">Где показывать</label>
                                    @foreach(['show_living' => 'Квартиры для собственного проживания', 'show_renting' => 'Квартиры для сдачи в аренду', 'show_newbuilds' => 'Новостройки'] as $field => $label)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="{{ $field }}" id="{{ $field }}" value="1" @checked(old($field, $apartment->$field))>
                                            <label class="form-check-label" for="{{ $field }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Карточка в списках</div></div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="city" class="form-label">Город</label>
                                    <input type="text" class="form-control" name="city" id="city" placeholder="Златибор" maxlength="100" value="{{ $v('city') }}"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="floor" class="form-label">Этаж</label>
                                    <input type="text" class="form-control" name="floor" id="floor" placeholder="Приземье, 1-й этаж" maxlength="100" value="{{ $v('floor') }}"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="type" class="form-label">Тип</label>
                                    <input type="text" class="form-control" name="type" id="type" placeholder="Студия, Однокомнатный" maxlength="100" value="{{ $v('type') }}"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="area" class="form-label">Площадь, м²</label>
                                    <input type="number" class="form-control" name="area" id="area" step="0.01" min="0" placeholder="28" value="{{ $v('area') === null ? '' : rtrim(rtrim(number_format((float) $v('area'), 2, '.', ''), '0'), '.') }}"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="price" class="form-label">Цена, €</label>
                                    <input type="number" class="form-control" name="price" id="price" step="1" min="0" placeholder="75500" value="{{ $v('price') }}"/>
                                    <div class="form-text">Без пробелов и копеек. Пусто: цена не показывается.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="sort_order" class="form-label">Порядок</label>
                                    <input type="number" class="form-control" name="sort_order" id="sort_order" step="1" min="0" value="{{ $v('sort_order') ?? 0 }}"/>
                                    <div class="form-text">Меньше, значит раньше. Удобнее перетаскивать в списке.</div>
                                </div>
                                <div class="col-12">
                                    <label for="card_text" class="form-label">Текст карточки</label>
                                    <textarea class="form-control" name="card_text" id="card_text" rows="2" maxlength="1000" placeholder="Студия, 28 м². Цена и характеристики указаны по презентации проекта.">{{ $v('card_text') }}</textarea>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Страница апартамента</div></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="description" class="form-label">Описание под заголовком</label>
                                    <textarea class="form-control" name="description" id="description" rows="4" maxlength="5000">{{ $v('description') }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label for="description_short" class="form-label">Описание для телефона</label>
                                    <textarea class="form-control" name="description_short" id="description_short" rows="2" maxlength="1000">{{ $v('description_short') }}</textarea>
                                    <div class="form-text">На телефоне в макете текст короче. Пусто: берётся обычное описание.</div>
                                </div>
                                <div class="col-12">
                                    <label for="info_note" class="form-label">Примечание под таблицей «Информация об апартаменте»</label>
                                    <textarea class="form-control" name="info_note" id="info_note" rows="2" maxlength="1000">{{ $v('info_note') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="about_title" class="form-label">Блок «Об объекте»: заголовок</label>
                                    <input type="text" class="form-control" name="about_title" id="about_title" maxlength="255" value="{{ $v('about_title') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="consultation_title" class="form-label">Блок «Персональный подбор»: заголовок</label>
                                    <input type="text" class="form-control" name="consultation_title" id="consultation_title" maxlength="255" value="{{ $v('consultation_title') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="about_text" class="form-label">Блок «Об объекте»: текст</label>
                                    <textarea class="form-control" name="about_text" id="about_text" rows="3" maxlength="5000">{{ $v('about_text') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="features_text" class="form-label">Характеристики, по одной в строке</label>
                                    <textarea class="form-control" name="features_text" id="features_text" rows="7" maxlength="5000" placeholder="Кухня&#10;Ванная комната&#10;Французский балкон">{{ old('features_text', implode("\n", $apartment->features ?? [])) }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Блок «Данные»: подпись и значение</label>
                                    <div id="dataItems">
                                        @foreach(array_merge(array_values($items), count($items) < 3 ? array_fill(0, 3 - count($items), ['label' => '', 'value' => '']) : []) as $i => $item)
                                            <div class="input-group mb-2 js-data-item">
                                                <input type="text" class="form-control" name="data_items[{{ $i }}][label]" placeholder="Меблировка" maxlength="100" value="{{ $item['label'] ?? '' }}"/>
                                                <input type="text" class="form-control" name="data_items[{{ $i }}][value]" placeholder="Полностью меблирован" maxlength="255" value="{{ $item['value'] ?? '' }}"/>
                                                <button type="button" class="btn btn-outline-danger js-remove-item" title="Убрать строку"><i class="bx bx-x"></i></button>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="addDataItem"><i class="bx bx-plus me-1"></i>Ещё строка</button>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Для поисковиков</div></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="meta_title" class="form-label">Заголовок вкладки</label>
                                    <input type="text" class="form-control" name="meta_title" id="meta_title" maxlength="255" placeholder="Апартамент A4 в SVETOGORSKA 27, Златибор: ИКАН" value="{{ $v('meta_title') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="meta_description" class="form-label">Описание для поиска</label>
                                    <textarea class="form-control" name="meta_description" id="meta_description" rows="2" maxlength="500">{{ $v('meta_description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card mb-4">
                        <h5 class="card-header">Фотографии</h5>
                        <div class="card-body">
                            @if(!$apartment->exists)
                                <p class="text-muted">Фотографии можно добавить сразу или после сохранения.</p>
                            @endif
                            @foreach($single as $field => $meta)
                                @php $photo = $apartment->exists ? $apartment->photo($meta['role']) : null; @endphp
                                <div class="mb-4 js-photo-field">
                                    <label for="{{ $field }}" class="form-label">{{ $meta['label'] }}</label>
                                    @if($photo)
                                        <div class="ikan-photo mb-2">
                                            <img src="{{ $photo->url() }}" alt="{{ $photo->alt }}" class="rounded w-100">
                                            <input type="text" class="form-control form-control-sm mt-2" name="photo_alt[{{ $photo->id }}]" maxlength="255" placeholder="Подпись для незрячих и поиска" value="{{ $photo->alt }}"/>
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="delete_photo[]" value="{{ $photo->id }}" id="delete_{{ $photo->id }}">
                                                <label class="form-check-label text-danger" for="delete_{{ $photo->id }}">Удалить это фото</label>
                                            </div>
                                        </div>
                                    @endif
                                    <input class="form-control js-preview-input" type="file" accept="image/*" id="{{ $field }}" name="{{ $field }}">
                                    <div class="form-text">{{ $meta['hint'] }}. {{ $photo ? 'Новый файл заменит текущий.' : '' }}</div>
                                    <img class="js-preview rounded w-100 mt-2 d-none" alt="">
                                </div>
                            @endforeach

                            <div class="divider text-start"><div class="divider-text">Галерея страницы</div></div>
                            <p class="form-text mt-0">Четыре первых фото стоят вверху страницы на компьютере. Перетащите, чтобы поменять порядок.</p>
                            @if($gallery->isNotEmpty())
                                <div id="gallerySortable" class="list-group mb-3">
                                    @foreach($gallery as $photo)
                                        <div class="list-group-item d-flex align-items-center gap-2 js-gallery-item" data-id="{{ $photo->id }}">
                                            <i class="bx bx-grid-vertical js-handle fs-5 text-muted" style="cursor: grab" title="Перетащить"></i>
                                            <img src="{{ $photo->url() }}" alt="{{ $photo->alt }}" class="rounded flex-shrink-0" style="width: 72px; height: 54px; object-fit: cover">
                                            <input type="hidden" name="photo_order[]" value="{{ $photo->id }}">
                                            <div class="flex-grow-1 min-w-0">
                                                <input type="text" class="form-control form-control-sm" name="photo_alt[{{ $photo->id }}]" maxlength="255" placeholder="Подпись" value="{{ $photo->alt }}"/>
                                                <div class="form-check mt-1 mb-0">
                                                    <input class="form-check-input" type="checkbox" name="delete_photo[]" value="{{ $photo->id }}" id="delete_{{ $photo->id }}">
                                                    <label class="form-check-label text-danger small" for="delete_{{ $photo->id }}">Удалить</label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <input class="form-control js-preview-input" type="file" accept="image/*" name="gallery[]" id="gallery" multiple>
                            <div class="form-text">Можно выбрать несколько файлов, они встанут в конец. JPG, PNG или WebP до 10 МБ каждый.</div>
                            <div class="js-preview-list row g-2 mt-0"></div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-dark">Сохранить</button>
                            <a href="{{ route('admin.apartments.index') }}" class="btn btn-outline-secondary">К списку</a>
                            @if($apartment->exists)
                                <button type="submit" form="deleteForm" class="btn btn-outline-danger ms-auto js-confirm" data-confirm="Удалить «{{ $apartment->name }}» вместе с фотографиями?">Удалить</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
        @if($apartment->exists)
            <form id="deleteForm" action="{{ route('admin.apartments.destroy', $apartment) }}" method="POST">@csrf @method('DELETE')</form>
        @endif
    </div>
@endsection
