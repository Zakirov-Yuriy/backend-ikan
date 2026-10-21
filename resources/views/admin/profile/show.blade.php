@extends('admin.layout')
@section('title', 'Мой профиль')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-10 col-xl-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Мой профиль</h5>
                        <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary btn-sm">
                            <i class="bx bx-edit-alt me-1"></i> Изменить
                        </a>
                    </div>
                    <div class="card-body">
                        @include('admin.notifications')
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Имя</dt>
                            <dd class="col-sm-8">{{ $user->name }}</dd>
                            <dt class="col-sm-4">Почта для входа</dt>
                            <dd class="col-sm-8">{{ $user->email }}</dd>
                            <dt class="col-sm-4">Пароль</dt>
                            <dd class="col-sm-8">••••••••</dd>
                            <dt class="col-sm-4">Создан</dt>
                            <dd class="col-sm-8">{{ $user->created_at?->timezone('Europe/Moscow')->format('d.m.Y H:i') }}</dd>
                            <dt class="col-sm-4">Изменён</dt>
                            <dd class="col-sm-8 mb-0">{{ $user->updated_at?->timezone('Europe/Moscow')->format('d.m.Y H:i') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
