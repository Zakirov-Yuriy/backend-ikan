@extends('admin.layout')
@section('title', 'Жилые комплексы')
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
                <h5 class="mb-0">Жилые комплексы</h5>
                <a class="btn btn-info" href="{{ route('admin.complexes.create') }}"><i class="menu-icon tf-icons bx bx-plus m-0"></i>Добавить ЖК</a>
            </div>
            <div class="card-body">
                @include('admin.notifications')
                @if($complexes->isEmpty())
                    <p class="mb-0">Жилых комплексов пока нет.</p>
                @else
                    <p class="text-muted mb-0">Порядок строк это порядок вкладок на сайте. Перетащите строку за значок слева, порядок сохранится сам.</p>
                @endif
            </div>
            @if($complexes->isNotEmpty())
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead class="table-light">
                        <tr>
                            <th><i class="bx bx-sort-alt-2"></i></th>
                            <th>Название</th>
                            <th>Адрес</th>
                            <th>Апартаментов</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0" id="sortable" data-sort-url="{{ route('admin.complexes.sort') }}">
                        @foreach($complexes as $complex)
                            <tr data-id="{{ $complex->id }}">
                                <td class="js-handle" style="cursor: grab"><i class="bx bx-grid-vertical"></i></td>
                                <td><a href="{{ route('admin.complexes.edit', $complex) }}">{{ $complex->name }}</a></td>
                                <td><span class="badge bg-secondary">{{ $complex->slug }}</span></td>
                                <td>
                                    <a href="{{ route('admin.apartments.index', ['complex' => $complex->id]) }}">{{ $complex->apartments_count }}</a>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('admin.complexes.edit', $complex) }}"><i class="bx bx-edit-alt me-1"></i> Изменить</a>
                                            <a class="dropdown-item" href="{{ route('admin.apartments.create', ['complex' => $complex->id]) }}"><i class="bx bx-plus me-1"></i> Добавить апартамент</a>
                                            <form action="{{ route('admin.complexes.destroy', $complex) }}" method="POST">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="dropdown-item js-confirm" data-confirm="Удалить жилой комплекс «{{ $complex->name }}»?"><i class="bx bx-trash me-1"></i> Удалить</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
