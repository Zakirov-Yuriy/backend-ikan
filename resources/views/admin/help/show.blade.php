@extends('admin.layout')
@section('title', $current ? 'Инструкция: '.$current['title'] : 'Инструкция')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        @if(! $current)
            <div class="card">
                <div class="card-body">
                    <h5>Инструкция</h5>
                    <p class="mb-0">Файл инструкции <code>docs/ADMIN.md</code> не найден на сервере. Напишите разработчику.</p>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-lg-3 mb-4">
                    <div class="card">
                        <div class="card-header pb-2">
                            <h5 class="mb-0">Инструкция</h5>
                            <div class="form-text">Как работать в админке, по разделам.</div>
                        </div>
                        <div class="list-group list-group-flush ikan-help-nav">
                            @foreach($sections as $item)
                                <a href="{{ route('admin.help', $item['slug']) }}"
                                   class="list-group-item list-group-item-action{{ $item['slug'] === $current['slug'] ? ' active' : '' }}">
                                    {{ $item['title'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-lg-9">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">{{ $current['title'] }}</h5>
                            @if($current['route'])
                                <a href="{{ route($current['route']) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bx bx-link-external me-1"></i> Открыть раздел
                                </a>
                            @endif
                        </div>
                        <div class="card-body ikan-help">
                            {!! $current['html'] !!}
                        </div>
                        <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
                            @if($previous)
                                <a href="{{ route('admin.help', $previous['slug']) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bx bx-chevron-left"></i> {{ $previous['title'] }}
                                </a>
                            @else
                                <span></span>
                            @endif
                            @if($next)
                                <a href="{{ route('admin.help', $next['slug']) }}" class="btn btn-outline-secondary btn-sm">
                                    {{ $next['title'] }} <i class="bx bx-chevron-right"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
