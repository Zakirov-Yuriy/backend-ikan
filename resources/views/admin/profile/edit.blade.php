@extends('admin.layout')
@section('title', 'Настройки профиля')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-md-10 col-xl-6">
                <div class="card mb-4">
                    <h5 class="card-header">Настройки профиля</h5>
                    <form action="{{ route('admin.profile.update') }}" method="POST">
                        @csrf @method('PUT')
                        <div class="card-body">
                            @include('admin.notifications')
                            <div class="mb-3">
                                <label for="name" class="form-label">Имя*</label>
                                <input type="text" class="form-control" name="name" id="name" required maxlength="100" value="{{ old('name', $user->name) }}"/>
                                <div class="form-text">Видно в меню профиля в правом верхнем углу.</div>
                            </div>
                            <div class="mb-4">
                                <label for="email" class="form-label">Почта для входа*</label>
                                <input type="email" class="form-control" name="email" id="email" required maxlength="255" autocomplete="username" value="{{ old('email', $user->email) }}"/>
                                <div class="form-text">С этой почтой входят в админку. Уведомления о заявках настраиваются отдельно, в «Настройках».</div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Смена пароля</div></div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Новый пароль</label>
                                <input type="password" class="form-control" name="password" id="password" minlength="8" maxlength="100" autocomplete="new-password"/>
                                <div class="form-text">Не короче 8 знаков. Если пароль менять не нужно, оставьте пустым.</div>
                            </div>
                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Повторите новый пароль</label>
                                <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" maxlength="100" autocomplete="new-password"/>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Подтверждение</div></div>
                            <div class="mb-4">
                                <label for="current_password" class="form-label">Текущий пароль</label>
                                <input type="password" class="form-control" name="current_password" id="current_password" maxlength="100" autocomplete="current-password"/>
                                <div class="form-text">Нужен, если меняете почту или пароль. Для смены имени не нужен.</div>
                            </div>

                            <button type="submit" class="btn btn-dark me-2">Сохранить</button>
                            <a href="{{ route('admin.profile.show') }}" class="btn btn-outline-secondary">Отмена</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
