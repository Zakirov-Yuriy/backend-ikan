@extends('admin.layout')
@php
    use App\Models\Photo;
    $scenario = $row->isScenario();
    $what = $scenario ? 'Строка направления' : 'Карточка «Наши направления»';
    $v = fn (string $field) => old($field, $row->$field);
    $anchor = $scenario ? '#scenarios' : '#directions';
@endphp
@section('title', $row->exists ? $what.': '.$row->title : 'Новая: '.$what)
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <form action="{{ $row->exists ? route('admin.home-rows.update', $row) : route('admin.home-rows.store') }}" method="POST" enctype="multipart/form-data" id="rowForm">
            @csrf
            @if($row->exists) @method('PUT') @else <input type="hidden" name="kind" value="{{ $row->kind }}"> @endif
            <div class="row">
                <div class="col-xl-8">
                    <div class="card mb-4">
                        <h5 class="card-header">{{ $row->exists ? $what : 'Новая: '.mb_strtolower($what) }}</h5>
                        <div class="card-body">
                            @include('admin.notifications')
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="title" class="form-label">Заголовок*</label>
                                    <input type="text" class="form-control" name="title" id="title" placeholder="{{ $scenario ? 'Покупка жилья для себя' : 'Жильё для себя' }}" required maxlength="255" value="{{ $v('title') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="text" class="form-label">Текст</label>
                                    <textarea class="form-control" name="text" id="text" rows="{{ $scenario ? 3 : 2 }}" maxlength="1000">{{ $v('text') }}</textarea>
                                    @unless($scenario)<div class="form-text">Одна строка под заголовком, например «Покупка квартиры для собственного проживания».</div>@endunless
                                </div>
                                @if($scenario)
                                    <div class="col-md-6">
                                        <label for="link_label" class="form-label">Текст ссылки*</label>
                                        <input type="text" class="form-control" name="link_label" id="link_label" placeholder="Смотреть квартиры для жизни" required maxlength="150" value="{{ $v('link_label') }}"/>
                                    </div>
                                @endif
                                <div class="col-md-6">
                                    <label for="link_href" class="form-label">{{ $scenario ? 'Ссылка ведёт на*' : 'Карточка ведёт на*' }}</label>
                                    @include('admin.partials.link-select', ['name' => 'link_href', 'id' => 'link_href', 'value' => $v('link_href'), 'groups' => $linkGroups])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card mb-4">
                        <h5 class="card-header">{{ $scenario ? 'Фотография справа' : 'Фотография карточки' }}</h5>
                        <div class="card-body">
                            @include('admin.partials.single-photo', [
                                'field' => 'photo',
                                'photo' => $row->exists ? $row->photo(Photo::ROLE_PHOTO) : null,
                                'label' => $row->exists ? 'Фотография' : 'Фотография*',
                                'hint' => $scenario ? 'Кадр примерно 3 на 2, на сайте 342 на 228 точек на компьютере.' : 'Вертикальный кадр, на сайте 246 на 324 точки; текст стоит поверх нижней части.',
                                'deletable' => false,
                            ])
                            @if($scenario)
                                @include('admin.partials.single-photo', [
                                    'field' => 'photo_wide',
                                    'photo' => $row->exists ? $row->photo(Photo::ROLE_PHOTO_WIDE) : null,
                                    'label' => 'Другой кадр для компьютера',
                                    'hint' => 'Необязательно. Если задан, на компьютере показывается он, на телефоне обычная фотография.',
                                ])
                            @endif
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-body d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-dark">Сохранить</button>
                            <a href="{{ route('admin.home-page.edit') }}{{ $anchor }}" class="btn btn-outline-secondary">Назад</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
