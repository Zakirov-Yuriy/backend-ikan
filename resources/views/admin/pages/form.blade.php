@extends('admin.layout')
@section('title', $page->name())
@php $v = fn (string $field) => old($field, $page->$field); @endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <form action="{{ route('admin.pages.update', $page->key) }}" method="POST" id="pageForm">
            @csrf @method('PUT')
            <div class="row">
                <div class="col-xl-8">
                    <div class="card mb-4">
                        <h5 class="card-header">{{ $page->name() }}</h5>
                        <div class="card-body">
                            @include('admin.notifications')
                            @if(!$page->exists)
                                <div class="alert alert-warning">Текстов этой страницы ещё нет: запустите <code>php artisan db:seed --class=HomeSeeder</code> или заполните форму и сохраните.</div>
                            @endif
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="title" class="form-label">Заголовок*</label>
                                    <input type="text" class="form-control" name="title" id="title" required maxlength="255" value="{{ $v('title') }}"/>
                                </div>
                                <div class="col-12">
                                    <label for="lead" class="form-label">Подзаголовок</label>
                                    <textarea class="form-control" name="lead" id="lead" rows="2" maxlength="1000">{{ $v('lead') }}</textarea>
                                </div>
                            </div>
                            <div class="divider text-start"><div class="divider-text">Блок «Персональный подбор» внизу страницы</div></div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="consultation_title" class="form-label">Заголовок</label>
                                    <input type="text" class="form-control" name="consultation_title" id="consultation_title" maxlength="255" placeholder="Как на главной" value="{{ $v('consultation_title') }}"/>
                                    <div class="form-text">Пусто: заголовок как на главной.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="consultation_title_mobile" class="form-label">Заголовок на телефоне</label>
                                    <input type="text" class="form-control" name="consultation_title_mobile" id="consultation_title_mobile" maxlength="255" placeholder="Такой же" value="{{ $v('consultation_title_mobile') }}"/>
                                    <div class="form-text">Пусто: на телефоне тот же заголовок, что на компьютере.</div>
                                </div>
                            </div>
                            <div class="divider text-start"><div class="divider-text">Для поисковиков</div></div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="meta_title" class="form-label">Заголовок вкладки</label>
                                    <input type="text" class="form-control" name="meta_title" id="meta_title" maxlength="255" value="{{ $v('meta_title') }}"/>
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
                        <div class="card-body d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-dark">Сохранить</button>
                            <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Назад</a>
                            <a href="{{ config('app.site_url', 'http://localhost:3000') }}{{ $page->siteUrl() }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Открыть на сайте</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
