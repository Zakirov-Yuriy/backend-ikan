@extends('admin.layout')
@section('title', 'Статистика')
@php
    $pct = fn ($v) => $v === null ? '–' : rtrim(rtrim(number_format($v, 1, ',', ''), '0'), ',').'%';
    $num = fn ($v) => number_format((int) $v, 0, ',', ' ');
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h4 class="mb-1">Статистика сайта</h4>
                <span class="text-muted">{{ $periods[$stats->period] }}: {{ $stats->periodText() }}</span>
            </div>
            <div class="btn-group flex-wrap" role="group" aria-label="Период">
                @foreach($periods as $key => $label)
                    <a href="{{ route('admin.stats', ['period' => $key]) }}" class="btn btn-sm {{ $stats->period === (string) $key ? 'btn-primary' : 'btn-outline-primary' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        @if($totals['views'] === 0)
            <div class="alert alert-info">За этот период просмотров пока нет. Они появляются, когда люди открывают сайт.</div>
        @endif

        <div class="row g-4 mb-4">
            @foreach([
                ['Посетители', $num($totals['visitors']), 'bx-user', 'люди за каждый день'],
                ['Просмотры страниц', $num($totals['views']), 'bx-show', 'все страницы сайта'],
                ['Открыли апартамент', $num($totals['apartmentViews']), 'bx-home-alt', 'просмотры страниц апартаментов'],
                ['Заявки', $num($totals['leads']), 'bx-envelope', 'из подвала и окна заявки'],
                ['Заявки от посетителей', $pct($totals['conversion']), 'bx-trending-up', 'сколько людей оставили заявку'],
            ] as [$label, $value, $icon, $hint])
                <div class="col-6 col-xl">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="avatar flex-shrink-0 mb-3">
                                <span class="avatar-initial rounded bg-label-primary"><i class="bx {{ $icon }}"></i></span>
                            </div>
                            <span class="d-block mb-1">{{ $label }}</span>
                            <h3 class="card-title mb-1">{{ $value }}</h3>
                            <small class="text-muted">{{ $hint }}</small>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <h5 class="mb-1">Посещения по дням</h5>
                        <span class="badge bg-label-warning">{{ $periods[$stats->period] }}</span>
                    </div>
                    <h6 class="mb-0">Всего посетителей: {{ $num($totals['visitors']) }}</h6>
                </div>
                <div id="statsChart" data-chart='@json($daily)'></div>
            </div>
        </div>

        <div class="card mb-4">
            <h5 class="card-header">Апартаменты: какие смотрят</h5>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="statsApartments">
                    <thead class="table-light">
                    <tr>
                        <th>Апартамент</th><th>ЖК</th>
                        <th class="text-end text-nowrap">Люди</th><th class="text-end text-nowrap">Просмотры</th><th class="text-end text-nowrap">Фото</th>
                        <th class="text-end text-nowrap">Заявки</th><th class="text-end text-nowrap">Заявки, %</th><th class="text-end text-nowrap">С телефона</th>
                        <th class="text-nowrap">Откуда открывали</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($apartments as $row)
                        <tr>
                            <td class="text-nowrap"><a href="{{ route('admin.apartments.edit', $row['apartment']) }}">{{ $row['apartment']->name }}</a></td>
                            <td><span class="badge bg-label-secondary">{{ $row['apartment']->complex?->name ?? '–' }}</span></td>
                            <td class="text-end fw-semibold">{{ $num($row['visitors']) }}</td>
                            <td class="text-end">{{ $num($row['views']) }}</td>
                            <td class="text-end">{{ $num($row['photos']) }}</td>
                            <td class="text-end">{{ $num($row['leads']) }}</td>
                            <td class="text-end">{{ $pct($row['conversion']) }}</td>
                            <td class="text-end">{{ $pct($row['mobile']) }}</td>
                            <td>
                                @forelse($row['from'] as $from)
                                    <span class="badge bg-label-primary me-1 mb-1">{{ $from['label'] }}: {{ $from['count'] }}</span>
                                @empty
                                    <span class="text-muted">–</span>
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted">Апартаментов пока нет.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body pt-3"><small class="text-muted">«Люди»: сколько человек открыли страницу апартамента. «Фото»: просмотры страницы со всеми фото. «Заявки»: отправлены со страницы апартамента. «Откуда открывали»: с какого экрана сайта нажали на карточку; «Со стороны»: пришли сразу на страницу, по ссылке или из поиска.</small></div>
        </div>

        <div class="card mb-4">
            <h5 class="card-header">Экраны сайта</h5>
            <div class="table-responsive">
                    <table class="table mb-0" id="statsScreens">
                        <thead class="table-light">
                        <tr><th>Экран</th><th class="text-end text-nowrap">Люди</th><th class="text-end text-nowrap">Просмотры</th><th class="text-end text-nowrap">Заявки</th><th class="text-end text-nowrap">Заявки, %</th><th class="text-end text-nowrap">С телефона</th></tr>
                        </thead>
                        <tbody>
                        @foreach($screens as $row)
                            <tr>
                                <td class="text-nowrap">{{ $row['label'] }}</td>
                                <td class="text-end fw-semibold">{{ $num($row['visitors']) }}</td>
                                <td class="text-end">{{ $num($row['views']) }}</td>
                                <td class="text-end">{{ $num($row['leads']) }}</td>
                                <td class="text-end">{{ $pct($row['conversion']) }}</td>
                                <td class="text-end">{{ $pct($row['mobile']) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <h5 class="card-header">С каких экранов открывают карточки апартаментов</h5>
                    <div class="card-body">
                        @include('admin.stats.bars', ['rows' => $cardSources, 'value' => 'count', 'empty' => 'Карточки за этот период не открывали.'])
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <h5 class="card-header">Откуда пришли на сайт</h5>
                    <div class="card-body">
                        @include('admin.stats.bars', ['rows' => $sources, 'value' => 'visitors', 'empty' => 'Заходов за этот период нет.'])
                        <small class="text-muted d-block mt-3">По первой странице захода. «Прямой заход»: адрес набрали сами, открыли из закладки или приложение не передало, откуда переход.</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-4">
                <div class="card h-100">
                    <h5 class="card-header">Устройства: телефон или компьютер</h5>
                    <div class="card-body">
                        <div id="statsDevices" data-chart='@json($devices)'></div>
                        @include('admin.stats.bars', ['rows' => $devices, 'value' => 'visitors', 'empty' => 'Посетителей за этот период нет.'])
                    </div>
                </div>
            </div>
        </div>

        <p class="text-muted small mb-0">
            Как считается: без cookie и без хранения адресов посетителей. Человек считается один раз за день, поэтому за неделю тот, кто заходил три дня, это три посетителя.
            Роботы поисковиков не считаются. Дни по московскому времени.
        </p>
    </div>
@endsection

@push('script')
    <script src="{{ asset('admin_front/assets/vendor/libs/apex-charts/apexcharts.min.js') }}"></script>
    <script>
        (function () {
            var dark = function () { return document.documentElement.classList.contains('theme-dark'); };
            var colors = function () {
                return dark()
                    ? {text: '#a3a4cc', grid: '#444564', mode: 'dark'}
                    : {text: '#697a8d', grid: '#eceef1', mode: 'light'};
            };
            var daily = JSON.parse(document.getElementById('statsChart').dataset.chart);
            var labels = daily.days.map(function (d) {
                return new Date(d + 'T00:00:00').toLocaleDateString('ru-RU', {day: '2-digit', month: 'short'});
            });
            var base = function () {
                var c = colors();
                return {
                    chart: {foreColor: c.text, toolbar: {show: false}, background: 'transparent', fontFamily: 'inherit'},
                    theme: {mode: c.mode},
                    grid: {borderColor: c.grid}
                };
            };
            var line = new ApexCharts(document.getElementById('statsChart'), Object.assign(base(), {
                chart: Object.assign(base().chart, {type: 'area', height: 360, zoom: {enabled: false}}),
                series: [
                    {name: 'Посетители', type: 'area', data: daily.visitors},
                    {name: 'Просмотры страниц', type: 'area', data: daily.views},
                    {name: 'Заявки', type: 'line', data: daily.leads}
                ],
                colors: ['#03a9f4', '#696cff', '#ff3e1d'],
                fill: {type: ['gradient', 'gradient', 'solid'], gradient: {opacityFrom: 0.45, opacityTo: 0.05}},
                stroke: {curve: 'smooth', width: [3, 2, 2]},
                dataLabels: {enabled: daily.days.length <= 31 && window.innerWidth >= 768, enabledOnSeries: [0]},
                markers: {size: daily.days.length <= 31 ? 3 : 0},
                xaxis: {categories: labels, labels: {rotate: -45, rotateAlways: daily.days.length > 10}},
                yaxis: {opposite: true, min: 0, forceNiceScale: true, labels: {formatter: function (v) { return Math.round(v); }}},
                legend: {horizontalAlign: 'left'},
                tooltip: {shared: true, intersect: false}
            }));
            line.render();

            var devices = JSON.parse(document.getElementById('statsDevices').dataset.chart);
            var hasDevices = devices.some(function (d) { return d.visitors > 0; });
            var donut = null;
            if (hasDevices) {
                donut = new ApexCharts(document.getElementById('statsDevices'), Object.assign(base(), {
                    chart: Object.assign(base().chart, {type: 'donut', height: 230}),
                    series: devices.map(function (d) { return d.visitors; }),
                    labels: devices.map(function (d) { return d.label; }),
                    colors: ['#03a9f4', '#71dd37', '#696cff'],
                    legend: {show: false},
                    dataLabels: {enabled: false},
                    stroke: {colors: [dark() ? '#2b2c40' : '#fff']},
                    plotOptions: {pie: {donut: {size: '62%', labels: {show: true, total: {show: true, label: 'Людей'}}}}}
                }));
                donut.render();
            }

            /* Смена темы: перекрасить подписи и сетку */
            var toggle = document.getElementById('themeToggle');
            if (toggle) {
                toggle.addEventListener('click', function () {
                    setTimeout(function () {
                        line.updateOptions(base());
                        if (donut) donut.updateOptions(Object.assign(base(), {stroke: {colors: [dark() ? '#2b2c40' : '#fff']}}));
                    }, 0);
                });
            }
        })();
    </script>
@endpush
