@extends('admin.layout')
@section('title', 'Главная сайта')
@push('css')
    <link rel="stylesheet" href="{{ asset('admin_front/assets/css/jquery-ui.min.css') }}"/>
@endpush
@push('script')
    <script src="{{ asset('admin_front/assets/js/jquery-ui.min.js') }}"></script>
@endpush
@php
    use App\Models\HomePage;
    use App\Models\Photo;
    use App\Support\SitePages;
    $v = fn (string $field) => old($field, $home->$field);
    $features = array_values(old('about_features', $home->about_features ?? []));
    while (count($features) < 4) {
        $features[] = ['icon' => 'chart', 'label' => ''];
    }
    $siteUrl = config('app.site_url', 'http://localhost:3000');
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <form action="{{ route('admin.home-page.update') }}" method="POST" enctype="multipart/form-data" id="homeForm">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-xl-8">
                    <div class="card mb-4">
                        <h5 class="card-header">Тексты главной страницы</h5>
                        <div class="card-body">
                            @include('admin.notifications')
                            @if(!$home->exists)
                                <div class="alert alert-warning">Текстов главной ещё нет: запустите <code>php artisan db:seed --class=HomeSeeder</code>, и сюда попадут тексты с сайта. Или заполните форму и сохраните.</div>
                            @endif
                            <p class="text-muted">Порядок блоков такой же, как на сайте сверху вниз. Строки направлений и карточки «Наши направления» ниже на этой странице.</p>

                            <div class="divider text-start"><div class="divider-text">Первый экран</div></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="hero_eyebrow" class="form-label">Надпись над заголовком</label>
                                    <input type="text" class="form-control" name="hero_eyebrow" id="hero_eyebrow" placeholder="Недвижимость в Сербии" maxlength="255" value="{{ $v('hero_eyebrow') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="hero_side_label" class="form-label">Подпись списка справа</label>
                                    <input type="text" class="form-control" name="hero_side_label" id="hero_side_label" placeholder="Работаем на вашей стороне" maxlength="255" value="{{ $v('hero_side_label') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="hero_title" class="form-label">Заголовок, первая строка*</label>
                                    <input type="text" class="form-control" name="hero_title" id="hero_title" placeholder="Пространство для жизни." required maxlength="255" value="{{ $v('hero_title') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="hero_title_accent" class="form-label">Заголовок, вторая строка (голубая)</label>
                                    <input type="text" class="form-control" name="hero_title_accent" id="hero_title_accent" placeholder="Актив для будущего." maxlength="255" value="{{ $v('hero_title_accent') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="hero_lead" class="form-label">Текст под заголовком</label>
                                    <textarea class="form-control" name="hero_lead" id="hero_lead" rows="2" maxlength="1000">{{ $v('hero_lead') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="hero_primary_label" class="form-label">Тёмная кнопка*</label>
                                    <input type="text" class="form-control" name="hero_primary_label" id="hero_primary_label" placeholder="Смотреть объекты" required maxlength="100" value="{{ $v('hero_primary_label') }}"/>
                                    <div class="form-text">Прокручивает страницу к «Актуальным объектам».</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="hero_secondary_label" class="form-label">Светлая кнопка*</label>
                                    <input type="text" class="form-control" name="hero_secondary_label" id="hero_secondary_label" placeholder="Обсудить задачу" required maxlength="100" value="{{ $v('hero_secondary_label') }}"/>
                                    <div class="form-text">Открывает окно «Оставить заявку».</div>
                                </div>
                                <div class="col-12">
                                    <label for="hero_side_items_text" class="form-label">Список справа, по одному пункту в строке</label>
                                    <textarea class="form-control" name="hero_side_items_text" id="hero_side_items_text" rows="3" maxlength="1000">{{ old('hero_side_items_text', implode("\n", $home->hero_side_items ?? [])) }}</textarea>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">«О компании»</div></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="about_eyebrow" class="form-label">Надпись над заголовком</label>
                                    <input type="text" class="form-control" name="about_eyebrow" id="about_eyebrow" placeholder="О компании" maxlength="255" value="{{ $v('about_eyebrow') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="about_title" class="form-label">Заголовок</label>
                                    <input type="text" class="form-control" name="about_title" id="about_title" placeholder="Недвижимость и инвестиции в Сербии" maxlength="255" value="{{ $v('about_title') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="about_text" class="form-label">Текст справа от заголовка</label>
                                    <textarea class="form-control" name="about_text" id="about_text" rows="2" maxlength="2000">{{ $v('about_text') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="about_features_label" class="form-label">Подпись над преимуществами</label>
                                    <input type="text" class="form-control" name="about_features_label" id="about_features_label" placeholder="Почему инвестируют в Сербию" maxlength="255" value="{{ $v('about_features_label') }}"/>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Преимущества: значок и подпись</label>
                                    <div class="js-rows">
                                        <div class="js-rows-list">
                                            @foreach($features as $i => $feature)
                                                <div class="input-group mb-2 js-row">
                                                    <select class="form-select" name="about_features[{{ $i }}][icon]" style="max-width: 130px" aria-label="Значок">
                                                        @foreach(HomePage::ICONS as $icon => $iconLabel)
                                                            <option value="{{ $icon }}" @selected(($feature['icon'] ?? '') === $icon)>{{ $iconLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="text" class="form-control" name="about_features[{{ $i }}][label]" placeholder="Высокий туристический спрос" maxlength="100" value="{{ $feature['label'] ?? '' }}"/>
                                                    <button type="button" class="btn btn-outline-danger js-remove-row" title="Убрать строку"><i class="bx bx-x"></i></button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <template>
                                            <div class="input-group mb-2 js-row">
                                                <select class="form-select" name="about_features[__INDEX__][icon]" style="max-width: 130px" aria-label="Значок">
                                                    @foreach(HomePage::ICONS as $icon => $iconLabel)
                                                        <option value="{{ $icon }}">{{ $iconLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <input type="text" class="form-control" name="about_features[__INDEX__][label]" placeholder="Преимущество" maxlength="100"/>
                                                <button type="button" class="btn btn-outline-danger js-remove-row" title="Убрать строку"><i class="bx bx-x"></i></button>
                                            </div>
                                        </template>
                                        <button type="button" class="btn btn-outline-secondary btn-sm js-add-row"><i class="bx bx-plus me-1"></i>Ещё строка</button>
                                    </div>
                                    <div class="form-text">На компьютере в ряд помещаются четыре, пустые строки не сохраняются.</div>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">«Наши направления»</div></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="directions_label" class="form-label">Подпись над карточками</label>
                                    <input type="text" class="form-control" name="directions_label" id="directions_label" placeholder="Наши направления" maxlength="255" value="{{ $v('directions_label') }}"/>
                                    <div class="form-text">Сами карточки ниже на этой странице.</div>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">«Актуальные объекты»</div></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="objects_eyebrow" class="form-label">Надпись над заголовком</label>
                                    <input type="text" class="form-control" name="objects_eyebrow" id="objects_eyebrow" placeholder="Проверенная недвижимость" maxlength="255" value="{{ $v('objects_eyebrow') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="objects_title" class="form-label">Заголовок</label>
                                    <input type="text" class="form-control" name="objects_title" id="objects_title" placeholder="Актуальные объекты" maxlength="255" value="{{ $v('objects_title') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="objects_all_label" class="form-label">Ссылка справа от заголовка</label>
                                    <input type="text" class="form-control" name="objects_all_label" id="objects_all_label" placeholder="Весь каталог" maxlength="100" value="{{ $v('objects_all_label') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="objects_all_href" class="form-label">Куда она ведёт</label>
                                    @include('admin.partials.link-select', ['name' => 'objects_all_href', 'id' => 'objects_all_href', 'value' => $v('objects_all_href'), 'groups' => $linkGroups])
                                </div>
                                <div class="col-md-6">
                                    <label for="objects_more_label" class="form-label">Ссылка в карточке</label>
                                    <input type="text" class="form-control" name="objects_more_label" id="objects_more_label" placeholder="Подробнее" maxlength="100" value="{{ $v('objects_more_label') }}"/>
                                    <div class="form-text">Какие апартаменты показывать: галочка «На главной» в форме апартамента, список ниже.</div>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">«Персональный подбор»</div></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="consultation_eyebrow" class="form-label">Надпись над заголовком</label>
                                    <input type="text" class="form-control" name="consultation_eyebrow" id="consultation_eyebrow" placeholder="Персональный подбор" maxlength="255" value="{{ $v('consultation_eyebrow') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="consultation_title" class="form-label">Заголовок</label>
                                    <input type="text" class="form-control" name="consultation_title" id="consultation_title" placeholder="Подберём объект под ваши цели" maxlength="255" value="{{ $v('consultation_title') }}"/>
                                </div>
                                <div class="col-md-6">
                                    <label for="consultation_text" class="form-label">Текст</label>
                                    <textarea class="form-control" name="consultation_text" id="consultation_text" rows="2" maxlength="1000">{{ $v('consultation_text') }}</textarea>
                                </div>
                                <div class="col-md-6">
                                    <label for="consultation_button_label" class="form-label">Кнопка</label>
                                    <input type="text" class="form-control" name="consultation_button_label" id="consultation_button_label" placeholder="Получить консультацию" maxlength="100" value="{{ $v('consultation_button_label') }}"/>
                                    <div class="form-text">Прокручивает страницу к форме «Заказать обратный звонок» в подвале.</div>
                                </div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Для поисковиков</div></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="meta_title" class="form-label">Заголовок вкладки</label>
                                    <input type="text" class="form-control" name="meta_title" id="meta_title" maxlength="255" placeholder="ИКАН: недвижимость и инвестиции в Сербии" value="{{ $v('meta_title') }}"/>
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
                        <h5 class="card-header">Фотография блока «Персональный подбор»</h5>
                        <div class="card-body">
                            @include('admin.partials.single-photo', ['field' => 'consultation_photo', 'photo' => $home->exists ? $home->photo(Photo::ROLE_CONSULTATION) : null, 'label' => 'Фон блока', 'hint' => 'Широкий кадр, на сайте 1024 на 260 точек на компьютере.'])
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-body d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-dark">Сохранить</button>
                            <a href="{{ $siteUrl }}/" target="_blank" rel="noopener" class="btn btn-outline-secondary">Открыть на сайте</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card mb-4" id="scenarios">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Строки направлений</h5>
                <a class="btn btn-info" href="{{ route('admin.home-rows.create', ['kind' => 'scenario']) }}"><i class="menu-icon tf-icons bx bx-plus m-0"></i>Добавить строку</a>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">Пять строк с номерами под первым экраном: текст слева, фотография справа. Порядок строк это порядок на сайте, перетащите строку за значок слева.</p>
            </div>
            @include('admin.home-page.rows-table', ['rows' => $scenarios, 'kind' => 'scenario'])
        </div>

        <div class="card mb-4" id="directions">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Карточки «Наши направления»</h5>
                <a class="btn btn-info" href="{{ route('admin.home-rows.create', ['kind' => 'direction']) }}"><i class="menu-icon tf-icons bx bx-plus m-0"></i>Добавить карточку</a>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">Тёмные карточки с фотографией, на компьютере в ряд помещаются четыре. Карточка нажимается целиком и ведёт на выбранную страницу.</p>
            </div>
            @include('admin.home-page.rows-table', ['rows' => $directions, 'kind' => 'direction'])
        </div>

        <div class="card mb-4" id="objects">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Актуальные объекты</h5>
                <a class="btn btn-outline-secondary" href="{{ route('admin.apartments.index') }}">Все апартаменты</a>
            </div>
            <div class="card-body">
                @if($objects->isEmpty())
                    <p class="mb-0">Пока ни у одного апартамента нет галочки «На главной, в «Актуальных объектах»». Поставьте её в форме апартамента.</p>
                @else
                    <p class="text-muted mb-0">Апартаменты с галочкой «На главной» в форме апартамента, в порядке списка апартаментов. Карточка берёт фото, цену и описание апартамента.</p>
                @endif
            </div>
            @if($objects->isNotEmpty())
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead class="table-light"><tr><th>Апартамент</th><th>ЖК</th><th>Стадия</th><th>Цена</th><th></th></tr></thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($objects as $apartment)
                            <tr>
                                <td><a href="{{ route('admin.apartments.edit', $apartment) }}">{{ $apartment->name }}</a></td>
                                <td><span class="badge bg-label-secondary">{{ $apartment->complex->name }}</span></td>
                                <td>{{ $apartment->status->label() }}</td>
                                <td>{{ $apartment->price === null ? '' : number_format($apartment->price, 0, '', ' ').' €' }}</td>
                                <td><a href="{{ route('admin.apartments.edit', $apartment) }}"><i class="bx bx-edit-alt me-1"></i>Изменить</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
