@extends('admin.layout')
@section('title', 'Апартаменты')
@push('css')
    <link rel="stylesheet" href="{{ asset('admin_front/assets/css/jquery-ui.min.css') }}"/>
@endpush
@push('script')
    <script src="{{ asset('admin_front/assets/js/jquery-ui.min.js') }}"></script>
@endpush
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Апартаменты</h5>
                <a class="btn btn-info" href="{{ route('admin.apartments.create', array_filter(['complex' => $filters['complex'] ?? null])) }}"><i class="menu-icon tf-icons bx bx-plus m-0"></i>Добавить апартамент</a>
            </div>
            <div class="card-body">
                @include('admin.notifications')
                <form action="{{ route('admin.apartments.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2">
                    <select class="form-select w-auto" name="complex" onchange="this.form.submit()">
                        <option value="">Все ЖК</option>
                        @foreach($complexes as $complex)
                            <option value="{{ $complex->id }}" @selected(($filters['complex'] ?? null) == $complex->id)>{{ $complex->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" class="form-control w-px-250" name="search" placeholder="Искать по названию" value="{{ $filters['search'] ?? '' }}"/>
                    <button type="submit" class="btn btn-dark"><i class="bx bx-search"></i></button>
                    @if(!empty($filters['search']) || !empty($filters['complex']))
                        <a class="btn btn-outline-secondary" href="{{ route('admin.apartments.index') }}">Сбросить</a>
                    @endif
                </form>
                @if($apartments->isEmpty())
                    <p class="mt-3 mb-0">Апартаментов не найдено.</p>
                @elseif(empty($filters['search']) && empty($filters['complex']) && !$apartments->hasPages())
                    <p class="text-muted mt-3 mb-0">Порядок строк это порядок карточек на сайте внутри ЖК. Перетащите строку за значок слева.</p>
                @endif
            </div>
            @if($apartments->isNotEmpty())
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead class="table-light">
                        <tr>
                            <th><i class="bx bx-sort-alt-2"></i></th>
                            <th>Фото</th>
                            <th>Название</th>
                            <th>ЖК</th>
                            <th>Площадь</th>
                            <th>Цена</th>
                            <th>Стадия</th>
                            <th>Где показан</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0" @if(empty($filters['search']) && empty($filters['complex']) && !$apartments->hasPages()) id="sortable" data-sort-url="{{ route('admin.apartments.sort') }}" @endif>
                        @foreach($apartments as $apartment)
                            @php $card = $apartment->photo(\App\Models\Photo::ROLE_CARD); @endphp
                            <tr data-id="{{ $apartment->id }}">
                                <td class="js-handle" style="cursor: grab"><i class="bx bx-grid-vertical"></i></td>
                                <td>
                                    @if($card)
                                        <img src="{{ $card->url() }}" alt="" class="rounded" style="width: 56px; height: 40px; object-fit: cover">
                                    @else
                                        <span class="badge bg-label-secondary">нет</span>
                                    @endif
                                </td>
                                <td><a href="{{ route('admin.apartments.edit', $apartment) }}">{{ $apartment->name }}</a><br><small class="text-muted">{{ implode(' · ', array_filter([$apartment->type, $apartment->floor])) }}</small></td>
                                <td><span class="badge bg-label-secondary">{{ $apartment->complex->name }}</span></td>
                                <td>{{ $apartment->area === null ? '' : rtrim(rtrim(number_format((float) $apartment->area, 2, '.', ''), '0'), '.').' м²' }}</td>
                                <td>{{ $apartment->price === null ? '' : number_format($apartment->price, 0, '', ' ').' €' }}</td>
                                <td><span class="badge {{ $apartment->status->value === 'ready' ? 'bg-label-success' : 'bg-label-warning' }}">{{ $apartment->status->label() }}</span></td>
                                <td>
                                    @foreach(['show_living' => 'для себя', 'show_renting' => 'аренда', 'show_newbuilds' => 'новостройки'] as $field => $label)
                                        @if($apartment->$field)<span class="badge bg-label-info me-1">{{ $label }}</span>@endif
                                    @endforeach
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('admin.apartments.edit', $apartment) }}"><i class="bx bx-edit-alt me-1"></i> Изменить</a>
                                            <form action="{{ route('admin.apartments.destroy', $apartment) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item js-confirm" data-confirm="Удалить «{{ $apartment->name }}» вместе с фотографиями?"><i class="bx bx-trash me-1"></i> Удалить</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($apartments->hasPages())
                    <div class="card-body">{{ $apartments->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
