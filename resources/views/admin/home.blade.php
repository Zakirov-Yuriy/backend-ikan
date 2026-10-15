@extends('admin.layout')
@section('title', 'Главная')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row g-4 mb-4">
            @foreach([
                ['Жилые комплексы', $complexes, 'bx-building-house', route('admin.complexes.index')],
                ['Апартаменты', $apartments, 'bx-home-alt', route('admin.apartments.index')],
                ['Строится', $building, 'bx-hard-hat', route('admin.apartments.index')],
                ['Готово к заселению', $ready, 'bx-check-circle', route('admin.apartments.index')],
                ['Новые заявки', $newLeads, 'bx-envelope', route('admin.leads.index', ['status' => 'new'])],
            ] as [$label, $count, $icon, $href])
                <div class="col-sm-6 col-xl">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="avatar flex-shrink-0 mb-3">
                                <span class="avatar-initial rounded bg-label-primary"><i class="bx {{ $icon }}"></i></span>
                            </div>
                            <span class="d-block mb-1">{{ $label }}</span>
                            <h3 class="card-title mb-2">{{ $count }}</h3>
                            <a href="{{ $href }}" class="small">Открыть раздел</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Последние изменения</h5>
                <a class="btn btn-info btn-sm" href="{{ route('admin.apartments.create') }}"><i class="bx bx-plus me-1"></i>Добавить апартамент</a>
            </div>
            @if($recent->isEmpty())
                <div class="card-body"><p class="mb-0">Апартаментов пока нет.</p></div>
            @else
                <div class="table-responsive text-nowrap">
                    <table class="table">
                        <thead class="table-light">
                        <tr><th>Апартамент</th><th>ЖК</th><th>Стадия</th><th>Изменён</th></tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($recent as $apartment)
                            <tr>
                                <td><a href="{{ route('admin.apartments.edit', $apartment) }}">{{ $apartment->name }}</a></td>
                                <td><span class="badge bg-label-secondary">{{ $apartment->complex->name }}</span></td>
                                <td><span class="badge {{ $apartment->status->value === 'ready' ? 'bg-label-success' : 'bg-label-warning' }}">{{ $apartment->status->label() }}</span></td>
                                <td>{{ $apartment->updated_at->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
