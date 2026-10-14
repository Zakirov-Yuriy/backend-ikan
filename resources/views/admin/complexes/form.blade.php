@extends('admin.layout')
@section('title', $complex->exists ? 'Изменить ЖК' : 'Новый ЖК')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-8 col-xl-6">
                <div class="card mb-4">
                    <h5 class="card-header">{{ $complex->exists ? 'Жилой комплекс «'.$complex->name.'»' : 'Новый жилой комплекс' }}</h5>
                    <form action="{{ $complex->exists ? route('admin.complexes.update', $complex) : route('admin.complexes.store') }}" method="POST">
                        @csrf
                        @if($complex->exists) @method('PUT') @endif
                        <div class="card-body">
                            @include('admin.notifications')
                            <div class="mb-3">
                                <label for="name" class="form-label">Название*</label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="L1" required maxlength="100"
                                       value="{{ old('name', $complex->name) }}"/>
                                <div class="form-text">Так ЖК подписан на вкладке и над группой карточек.</div>
                            </div>
                            <div class="mb-3">
                                <label for="slug" class="form-label">Адрес (латиницей)</label>
                                <input type="text" class="form-control" name="slug" id="slug" placeholder="l1" maxlength="100"
                                       value="{{ old('slug', $complex->slug) }}"/>
                                <div class="form-text">Служебное имя для API и адресов. Пустое заполнится из названия.</div>
                            </div>
                            <button type="submit" class="btn btn-dark me-2">Сохранить</button>
                            <a href="{{ route('admin.complexes.index') }}" class="btn btn-outline-secondary">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
