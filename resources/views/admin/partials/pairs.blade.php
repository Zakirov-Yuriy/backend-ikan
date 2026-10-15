{{--
    Строки «подпись и значение» с кнопкой «Ещё строка».
    $name: имя поля (features, data_items); $rows: текущие пары;
    $labelPlaceholder, $valuePlaceholder: подсказки; $min: сколько пустых строк показать.
--}}
@php
    $rows = array_values($rows ?? []);
    $min = $min ?? 3;
    if (count($rows) < $min) {
        $rows = array_merge($rows, array_fill(0, $min - count($rows), ['label' => '', 'value' => '']));
    }
@endphp
<div class="js-pairs" data-name="{{ $name }}" data-label-placeholder="{{ $labelPlaceholder ?? 'Подпись' }}" data-value-placeholder="{{ $valuePlaceholder ?? 'Значение' }}">
    <div class="js-pairs-list">
        @foreach($rows as $i => $row)
            <div class="input-group mb-2 js-pair">
                <input type="text" class="form-control" name="{{ $name }}[{{ $i }}][label]" placeholder="{{ $labelPlaceholder ?? 'Подпись' }}" maxlength="100" value="{{ $row['label'] ?? '' }}"/>
                <input type="text" class="form-control" name="{{ $name }}[{{ $i }}][value]" placeholder="{{ $valuePlaceholder ?? 'Значение' }}" maxlength="255" value="{{ $row['value'] ?? '' }}"/>
                <button type="button" class="btn btn-outline-danger js-remove-pair" title="Убрать строку"><i class="bx bx-x"></i></button>
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm js-add-pair"><i class="bx bx-plus me-1"></i>Ещё строка</button>
</div>
