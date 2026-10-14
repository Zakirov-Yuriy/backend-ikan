<!DOCTYPE html>
{{-- Страница входа, по образцу admin-project ЛИДЛЕ --}}
<html lang="ru" class="light-style" dir="ltr" data-theme="theme-default" data-assets-path="/admin_front/assets/" data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Админка ИКАН: вход</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/vendor/fonts/public-sans.css') }}"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/vendor/fonts/boxicons.css') }}"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/vendor/css/core.css') }}" class="template-customizer-core-css"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/css/style.css') }}"/>
</head>
<body>
<div class="m-auto container d-flex flex-column align-items-center justify-content-center" style="height: 100vh;">
    <div class="card col-md-4">
        <div class="card-body">
            <div class="app-brand justify-content-center mb-4">
                <span class="app-brand-logo"><i class="bx bx-buildings fs-2 text-primary"></i></span>
                <span class="app-brand-text demo fw-bolder ms-2 text-body">ИКАН</span>
            </div>
            <form action="{{ route('admin.login.attempt') }}" method="POST">
                @csrf
                @include('admin.notifications')
                <div class="mb-3">
                    <label for="formControlInputEmail" class="form-label">Почта</label>
                    <input type="email" class="form-control" name="email" id="formControlInputEmail"
                           placeholder="Почта" value="{{ old('email') }}" required autofocus/>
                </div>
                <div class="mb-3">
                    <label for="formControlInputPassword" class="form-label">Пароль</label>
                    <input type="password" class="form-control" name="password" id="formControlInputPassword"
                           placeholder="Пароль" required/>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="formControlCheckRemember" name="remember" value="1"/>
                    <label class="form-check-label" for="formControlCheckRemember">Запомнить меня</label>
                </div>
                <button type="submit" class="btn btn-dark">Войти</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
