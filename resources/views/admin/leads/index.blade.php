@extends('admin.layout')
@section('title', 'Заявки')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">Заявки с сайта @if($newCount)<span class="badge bg-label-danger ms-2">новых: {{ $newCount }}</span>@endif</h5>
                <div class="btn-group">
                    <a class="btn btn-sm {{ empty($filters['status']) ? 'btn-dark' : 'btn-outline-dark' }}" href="{{ route('admin.leads.index') }}">Все</a>
                    <a class="btn btn-sm {{ ($filters['status'] ?? '') === 'new' ? 'btn-dark' : 'btn-outline-dark' }}" href="{{ route('admin.leads.index', ['status' => 'new']) }}">Новые</a>
                    <a class="btn btn-sm {{ ($filters['status'] ?? '') === 'done' ? 'btn-dark' : 'btn-outline-dark' }}" href="{{ route('admin.leads.index', ['status' => 'done']) }}">Обработанные</a>
                </div>
            </div>
            <div class="card-body">
                @include('admin.notifications')
                @if($leads->isEmpty())
                    <p class="mb-0">Заявок пока нет. Они появляются здесь, когда посетитель отправляет форму «Заказать обратный звонок» или окно «Оставить заявку» на сайте.</p>
                @else
                    <p class="text-muted mb-0">Куда приходят уведомления о заявках: <a href="{{ route('admin.settings.edit') }}">Настройки</a>.</p>
                @endif
            </div>
            @if($leads->isNotEmpty())
                <div class="table-responsive">
                    <table class="table">
                        <thead class="table-light">
                        <tr>
                            <th>Когда</th>
                            <th>Имя и телефон</th>
                            <th>Что интересует</th>
                            <th>Откуда</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                        </thead>
                        <tbody class="table-border-bottom-0">
                        @foreach($leads as $lead)
                            <tr class="{{ $lead->isNew() ? 'fw-semibold' : '' }}">
                                <td class="text-nowrap">{{ $lead->created_at->format('d.m.Y') }}<br><small class="text-muted">{{ $lead->created_at->format('H:i') }}</small></td>
                                <td class="text-nowrap">{{ $lead->name }}<br><a href="tel:{{ preg_replace('/[^\d+]/', '', $lead->phone) }}">{{ $lead->phone }}</a></td>
                                <td style="max-width: 320px; white-space: normal">{{ $lead->interest ?: '' }}</td>
                                <td style="white-space: normal"><small>{{ $lead->sourceLabel() }}@if($lead->page)<br><a href="{{ config('app.site_url') }}{{ $lead->page }}" target="_blank" rel="noopener" class="text-muted">{{ $lead->page }}</a>@endif</small></td>
                                <td><span class="badge {{ $lead->isNew() ? 'bg-label-danger' : 'bg-label-success' }}">{{ $lead->isNew() ? 'Новая' : 'Обработана' }}</span></td>
                                <td class="text-nowrap">
                                    <form action="{{ route('admin.leads.toggle', $lead) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $lead->isNew() ? 'btn-outline-success' : 'btn-outline-secondary' }}">{{ $lead->isNew() ? 'Обработана' : 'Снова новая' }}</button>
                                    </form>
                                    <form action="{{ route('admin.leads.destroy', $lead) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger js-confirm" data-confirm="Удалить заявку от {{ $lead->name }}?"><i class="bx bx-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($leads->hasPages())
                    <div class="card-body">{{ $leads->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
