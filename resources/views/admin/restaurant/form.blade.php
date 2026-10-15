@extends('admin.layout')
@section('title', 'Ресторан')
@push('css')
    <link rel="stylesheet" href="{{ asset('admin_front/assets/css/jquery-ui.min.css') }}"/>
@endpush
@push('script')
    <script src="{{ asset('admin_front/assets/js/jquery-ui.min.js') }}"></script>
@endpush
@php
    use App\Models\Photo;
    $v = fn (string $field) => old($field, $restaurant->$field);
    $gallery = $restaurant->exists ? $restaurant->photos->where('role', Photo::ROLE_GALLERY)->values() : collect();
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <form action="{{ route('admin.restaurant.update') }}" method="POST" enctype="multipart/form-data" id="restaurantForm">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-xl-8">
                    <div class="card mb-4">
                        <h5 class="card-header">Страница «Ресторан»</h5>
                        <div class="card-body">
                            @include('admin.notifications')
                            @if(!$restaurant->exists)
                                <div class="alert alert-warning">Записи ресторана ещё нет: заполните форму и сохраните, страница на сайте появится.</div>
                            @endif
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Заголовок страницы*</label>
                                    <input type="text" class="form-control" name="title" id="title" placeholder="Арендуйте ресторан" required maxlength="150" value="{{ $v('title') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Название объекта в карточке*</label>
                                    <input type="text" class="form-control" name="name" id="name" placeholder="Ресторан" required maxlength="150" value="{{ $v('name') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="lead" class="form-label">Подзаголовок</label>
                                    <textarea class="form-control" name="lead" id="lead" rows="2" maxlength="1000">{{ $v('lead') }}</textarea>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Карточка вверху страницы</div></div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="status" class="form-label">Статус</label>
                                    <input type="text" class="form-control" name="status" id="status" placeholder="Готов" maxlength="100" value="{{ $v('status') }}"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="city" class="form-label">Город</label>
                                    <input type="text" class="form-control" name="city" id="city" placeholder="Златибор" maxlength="100" value="{{ $v('city') }}"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="floor" class="form-label">Этаж</label>
                                    <input type="text" class="form-control" name="floor" id="floor" placeholder="Приземье" maxlength="100" value="{{ $v('floor') }}"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="type" class="form-label">Тип</label>
                                    <input type="text" class="form-control" name="type" id="type" placeholder="Ресторан" maxlength="100" value="{{ $v('type') }}"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="area" class="form-label">Площадь, м²</label>
                                    <input type="number" class="form-control" name="area" id="area" step="0.01" min="0" placeholder="100" value="{{ $v('area') === null ? '' : rtrim(rtrim(number_format((float) $v('area'), 2, '.', ''), '0'), '.') }}"/>
                                </div>
                                <div class="col-md-4">
                                    <label for="price_text" class="form-label">Стоимость, словами</label>
                                    <input type="text" class="form-control" name="price_text" id="price_text" placeholder="Узнать" maxlength="100" value="{{ $v('price_text') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="description" class="form-label">Описание</label>
                                    <textarea class="form-control" name="description" id="description" rows="4" maxlength="5000">{{ $v('description') }}</textarea>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Таблица «Информация о ресторане»</div></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="info_type" class="form-label">Тип</label>
                                    <input type="text" class="form-control" name="info_type" id="info_type" placeholder="Коммерческое помещение" maxlength="150" value="{{ $v('info_type') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="info_purpose" class="form-label">Назначение</label>
                                    <input type="text" class="form-control" name="info_purpose" id="info_purpose" placeholder="Ресторанный бизнес" maxlength="150" value="{{ $v('info_purpose') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="info_price" class="form-label">Стоимость</label>
                                    <input type="text" class="form-control" name="info_price" id="info_price" placeholder="По запросу" maxlength="100" value="{{ $v('info_price') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="info_floor" class="form-label">Этаж</label>
                                    <input type="text" class="form-control" name="info_floor" id="info_floor" placeholder="Приземье" maxlength="100" value="{{ $v('info_floor') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="info_note" class="form-label">Примечание под таблицей</label>
                                    <textarea class="form-control" name="info_note" id="info_note" rows="2" maxlength="1000">{{ $v('info_note') }}</textarea>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Блоки «Об объекте» и «Данные»</div></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="about_title" class="form-label">«Об объекте»: заголовок</label>
                                    <input type="text" class="form-control" name="about_title" id="about_title" maxlength="255" value="{{ $v('about_title') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="consultation_title" class="form-label">«Персональный подбор»: заголовок</label>
                                    <input type="text" class="form-control" name="consultation_title" id="consultation_title" maxlength="255" value="{{ $v('consultation_title') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="about_text" class="form-label">«Об объекте»: текст</label>
                                    <textarea class="form-control" name="about_text" id="about_text" rows="3" maxlength="5000">{{ $v('about_text') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Характеристики: подпись и значение</label>
                                    @include('admin.partials.pairs', ['name' => 'features', 'rows' => old('features', $restaurant->features ?? []), 'labelPlaceholder' => 'Назначение', 'valuePlaceholder' => 'Ресторанный бизнес', 'min' => 3])
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">«Данные»: подпись и значение</label>
                                    @include('admin.partials.pairs', ['name' => 'data_items', 'rows' => old('data_items', $restaurant->data_items ?? []), 'labelPlaceholder' => 'Формат', 'valuePlaceholder' => 'Аренда для бизнеса', 'min' => 3])
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Для поисковиков</div></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="meta_title" class="form-label">Заголовок вкладки</label>
                                    <input type="text" class="form-control" name="meta_title" id="meta_title" maxlength="255" placeholder="Ресторан в аренду в Златиборе: ИКАН" value="{{ $v('meta_title') }}"/>
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
                            @include('admin.partials.single-photo', ['field' => 'photo_mobile', 'photo' => $restaurant->exists ? $restaurant->photo(Photo::ROLE_MOBILE) : null, 'label' => 'Фото страницы (телефон)', 'hint' => 'Вверху страницы на телефоне.'])
                            @include('admin.partials.gallery', ['gallery' => $gallery, 'hint' => 'Четыре первых фото стоят вверху страницы на компьютере.'])
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-body d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-dark">Сохранить</button>
                            <a href="{{ config('app.site_url', 'http://localhost:3000') }}/restoran" target="_blank" rel="noopener" class="btn btn-outline-secondary">Открыть на сайте</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
