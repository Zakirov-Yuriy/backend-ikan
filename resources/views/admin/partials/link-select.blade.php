{{--
    Адрес ссылки: страница сайта или апартамент из списка, либо любой адрес.
    В форму уходит одно поле $name; список только подставляет в него значение.
    $name, $id, $value, $groups (App\Support\SitePages::groups()).
--}}
@php
    $value = (string) ($value ?? '');
    $known = false;
    foreach ($groups as $options) {
        if (isset($options[$value])) {
            $known = true;
        }
    }
    $choice = $value === '' ? '' : ($known ? $value : 'custom');
@endphp
<div class="js-link">
    <select class="form-select js-link-select" id="{{ $id }}_choice" aria-label="Куда ведёт ссылка">
        <option value="" @selected($choice === '')>Выберите страницу</option>
        @foreach($groups as $group => $options)
            <optgroup label="{{ $group }}">
                @foreach($options as $href => $label)
                    <option value="{{ $href }}" @selected($choice === $href)>{{ $label }}</option>
                @endforeach
            </optgroup>
        @endforeach
        <option value="custom" @selected($choice === 'custom')>Другой адрес...</option>
    </select>
    <input type="text" class="form-control mt-2 js-link-custom{{ $choice === 'custom' ? '' : ' d-none' }}" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}" placeholder="/dlya-sebya или https://..." maxlength="255" autocomplete="off">
</div>
