{{--
    Одна фотография роли: текущая с подписью и удалением, поле для новой.
    $field: имя поля файла; $photo: текущая фотография или null; $label, $hint.
--}}
<div class="mb-4 js-photo-field">
    <label for="{{ $field }}" class="form-label">{{ $label }}</label>
    @if($photo)
        <div class="ikan-photo mb-2">
            <img src="{{ $photo->url() }}" alt="{{ $photo->alt }}" class="rounded w-100">
            <input type="text" class="form-control form-control-sm mt-2" name="photo_alt[{{ $photo->id }}]" maxlength="255" placeholder="Подпись для незрячих и поиска" value="{{ $photo->alt }}"/>
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="delete_photo[]" value="{{ $photo->id }}" id="delete_{{ $photo->id }}">
                <label class="form-check-label text-danger" for="delete_{{ $photo->id }}">Удалить это фото</label>
            </div>
        </div>
    @endif
    <input class="form-control js-preview-input js-shrink" type="file" accept="image/*" id="{{ $field }}" name="{{ $field }}">
    <div class="form-text">{{ $hint }}{{ $photo ? ' Новый файл заменит текущий.' : '' }}</div>
    <img class="js-preview rounded w-100 mt-2 d-none" alt="">
</div>
