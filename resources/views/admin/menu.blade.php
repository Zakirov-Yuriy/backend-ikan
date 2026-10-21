@php use App\Http\Controllers\Admin\MenuController as Menu; @endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('admin.home') }}" class="app-brand-link">
            <span class="app-brand-logo">
                @include('admin.mark')
            </span>
            <span class="app-brand-text demo menu-text fw-bolder ms-2">ИКАН</span>
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <li class="menu-item{{ Menu::active('admin.home') ? ' active' : '' }}">
            <a href="{{ route('admin.home') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bar-chart"></i>
                <div>Главная</div>
            </a>
        </li>

        <li class="menu-item{{ Menu::active('admin.stats') ? ' active' : '' }}">
            <a href="{{ route('admin.stats') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-line-chart"></i>
                <div>Статистика</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text">Объекты</span></li>

        <li class="menu-item{{ Menu::active('admin.complexes') ? ' active' : '' }}">
            <a href="{{ route('admin.complexes.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-building-house"></i>
                <div>Жилые комплексы</div>
            </a>
        </li>
        <li class="menu-item{{ Menu::active('admin.apartments') ? ' active' : '' }}">
            <a href="{{ route('admin.apartments.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-alt"></i>
                <div>Апартаменты</div>
            </a>
        </li>
        <li class="menu-item{{ Menu::active('admin.restaurant') ? ' active' : '' }}">
            <a href="{{ route('admin.restaurant.edit') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-restaurant"></i>
                <div>Ресторан</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text">Страницы сайта</span></li>

        <li class="menu-item{{ Menu::active(['admin.home-page', 'admin.home-rows']) ? ' active' : '' }}">
            <a href="{{ route('admin.home-page.edit') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-window-alt"></i>
                <div>Главная сайта</div>
            </a>
        </li>
        <li class="menu-item{{ Menu::active('admin.pages') ? ' active' : '' }}">
            <a href="{{ route('admin.pages.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-list-ul"></i>
                <div>Страницы списков</div>
            </a>
        </li>

        <li class="menu-header small text-uppercase"><span class="menu-header-text">Клиенты</span></li>

        @php $newLeads = \App\Models\Lead::query()->fresh()->count(); @endphp
        <li class="menu-item{{ Menu::active('admin.leads') ? ' active' : '' }}">
            <a href="{{ route('admin.leads.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-envelope"></i>
                <div>Заявки</div>
                @if($newLeads)<span class="badge bg-danger rounded-pill ms-auto">{{ $newLeads }}</span>@endif
            </a>
        </li>
        <li class="menu-item{{ Menu::active('admin.settings') ? ' active' : '' }}">
            <a href="{{ route('admin.settings.edit') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-cog"></i>
                <div>Настройки</div>
            </a>
        </li>
    </ul>
</aside>
