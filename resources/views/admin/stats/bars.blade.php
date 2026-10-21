{{-- Список с полосками: подпись, число, доля --}}
@if($rows->isEmpty() || $rows->sum($value) === 0)
    <p class="text-muted mb-0">{{ $empty }}</p>
@else
    @foreach($rows as $row)
        <div class="mb-3">
            <div class="d-flex justify-content-between gap-3 mb-1">
                <span>{{ $row['label'] }}</span>
                <span class="text-nowrap"><strong>{{ number_format($row[$value], 0, ',', ' ') }}</strong>
                    <span class="text-muted">{{ $row['share'] === null ? '' : rtrim(rtrim(number_format($row['share'], 1, ',', ''), '0'), ',').'%' }}</span></span>
            </div>
            <div class="progress" style="height: 6px">
                <div class="progress-bar" role="progressbar" style="width: {{ $row['share'] ?? 0 }}%"></div>
            </div>
        </div>
    @endforeach
@endif
