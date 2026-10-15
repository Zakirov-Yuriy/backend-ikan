@extends('admin.layout')
@section('title', 'Страницы списков')
@php $siteUrl = config('app.site_url', 'http://localhost:3000'); @endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Страницы списков</h5></div>
            <div class="card-body">
                @include('admin.notifications')
                <p class="text-muted mb-0">Заголовок, подзаголовок и блок подбора у трёх списков квартир. Сами карточки берутся из апартаментов по галочкам «Где показывать».</p>
            </div>
            <div class="table-responsive text-nowrap">
                <table class="table">
                    <thead class="table-light"><tr><th>Страница</th><th>Заголовок на сайте</th><th>Адрес</th><th>Действия</th></tr></thead>
                    <tbody class="table-border-bottom-0">
                    @foreach($pages as $key => $page)
                        <tr>
                            <td><a href="{{ route('admin.pages.edit', $key) }}">{{ $page->name() }}</a></td>
                            <td>{{ $page->exists ? $page->title : '' }}@unless($page->exists)<span class="badge bg-label-warning">не заполнена</span>@endunless</td>
                            <td><a href="{{ $siteUrl }}{{ $page->siteUrl() }}" target="_blank" rel="noopener">{{ $page->siteUrl() }}</a></td>
                            <td><a href="{{ route('admin.pages.edit', $key) }}"><i class="bx bx-edit-alt me-1"></i>Изменить</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
