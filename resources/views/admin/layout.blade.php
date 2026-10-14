<!DOCTYPE html>
{{--
    Общая обёртка админки. Оформление скопировано с admin-project ЛИДЛЕ
    (шаблон Sneat на Bootstrap 5): левое меню, верхняя полоса, тёмная тема.
    Файлы оформления лежат в public/admin_front.
--}}
<html lang="ru" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="/admin_front/assets/"
      data-template="vertical-menu-template-free">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}"/>
    <title>Админка ИКАН: @yield('title')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48"/>
    <link rel="icon" href="{{ asset('icon.svg') }}" type="image/svg+xml"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/vendor/fonts/public-sans.css') }}"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/vendor/fonts/boxicons.css') }}"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/vendor/css/core.css') }}" class="template-customizer-core-css"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/css/style.css') }}"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/css/dark-theme.css') }}"/>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/css/ikan.css') }}"/>
    {{-- Тёмная тема: класс ставится до отрисовки, чтобы не было вспышки светлого фона --}}
    <script>
        (function () {
            try {
                if (localStorage.getItem('admin-theme') === 'dark') {
                    document.documentElement.classList.add('theme-dark');
                }
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('admin_front/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}"/>
    <script src="{{ asset('admin_front/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('admin_front/assets/js/config.js') }}"></script>
    @stack('css')
</head>

<body>
<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">
        @include('admin.menu')
        <div class="layout-page">
            @include('admin.navbar')
            <div class="content-wrapper">
                @yield('content')
                <footer class="content-footer footer bg-footer-theme">
                    <div class="container-xxl d-flex flex-wrap justify-content-between py-2 flex-md-row flex-column"></div>
                </footer>
                <div class="content-backdrop fade"></div>
            </div>
        </div>
    </div>
    <div class="layout-overlay layout-menu-toggle"></div>
</div>

<script src="{{ asset('admin_front/assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('admin_front/assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('admin_front/assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('admin_front/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('admin_front/assets/vendor/js/menu.js') }}"></script>
<script src="{{ asset('admin_front/assets/js/main.js') }}"></script>
<script src="{{ asset('admin_front/assets/js/ikan.js') }}"></script>
@stack('script')
</body>
</html>
