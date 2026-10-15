{{--
    Галерея страницы: список с перетаскиванием, подписями и удалением,
    поле для новых файлов. $gallery: фотографии роли gallery; $hint: подсказка.
--}}
<div class="divider text-start"><div class="divider-text">Галерея страницы</div></div>
<p class="form-text mt-0">{{ $hint }} Перетащите за значок, чтобы поменять порядок.</p>
@if($gallery->isNotEmpty())
    <div id="gallerySortable" class="list-group mb-3">
        @foreach($gallery as $photo)
            <div class="list-group-item d-flex align-items-center gap-2 js-gallery-item" data-id="{{ $photo->id }}">
                <i class="bx bx-grid-vertical js-handle fs-5 text-muted" style="cursor: grab" title="Перетащить"></i>
                <img src="{{ $photo->url() }}" alt="{{ $photo->alt }}" class="rounded flex-shrink-0" style="width: 72px; height: 54px; object-fit: cover">
                <input type="hidden" name="photo_order[]" value="{{ $photo->id }}">
                <div class="flex-grow-1 min-w-0">
                    <input type="text" class="form-control form-control-sm" name="photo_alt[{{ $photo->id }}]" maxlength="255" placeholder="Подпись" value="{{ $photo->alt }}"/>
                    <div class="form-check mt-1 mb-0">
                        <input class="form-check-input" type="checkbox" name="delete_photo[]" value="{{ $photo->id }}" id="delete_{{ $photo->id }}">
                        <label class="form-check-label text-danger small" for="delete_{{ $photo->id }}">Удалить</label>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
<input class="form-control js-preview-input js-shrink" type="file" accept="image/*" name="gallery[]" id="gallery" multiple>
<div class="form-text">Можно выбрать несколько файлов, они встанут в конец. JPG, PNG или WebP; большие фото уменьшаются до 2000 px прямо в браузере.</div>
<div class="js-preview-list row g-2 mt-0"></div>
