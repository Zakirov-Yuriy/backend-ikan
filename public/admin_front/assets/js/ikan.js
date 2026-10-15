/* Сценарии админки ИКАН: тема, подтверждение удаления, порядок строк, фото, строки «Данных» */
'use strict';

(function () {
    /* Переключатель тёмной темы, выбор хранится в браузере (как в ЛИДЛЕ) */
    var root = document.documentElement;
    var toggle = document.getElementById('themeToggle');
    var icon = document.getElementById('themeToggleIcon');

    function syncIcon() {
        if (!icon) return;
        var dark = root.classList.contains('theme-dark');
        icon.classList.toggle('bx-sun', dark);
        icon.classList.toggle('bx-moon', !dark);
    }

    syncIcon();
    if (toggle) {
        toggle.addEventListener('click', function () {
            var dark = root.classList.toggle('theme-dark');
            try { localStorage.setItem('admin-theme', dark ? 'dark' : 'light'); } catch (e) {}
            syncIcon();
        });
    }

    /* Кнопки удаления спрашивают подтверждение */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.js-confirm');
        if (button && !window.confirm(button.dataset.confirm || 'Удалить?')) {
            event.preventDefault();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var csrf = document.querySelector('meta[name="csrf-token"]');
        var token = csrf ? csrf.content : (document.querySelector('input[name="_token"]') || {}).value;

        /* Порядок строк в таблицах: перетащил, порядок ушёл на сервер формой */
        document.querySelectorAll('#sortable, .js-sortable').forEach(function (sortable) {
            if (!window.jQuery || !jQuery.fn.sortable) return;
            jQuery(sortable).sortable({
                handle: '.js-handle',
                axis: 'y',
                update: function () {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = sortable.dataset.sortUrl;
                    var tokenInput = document.createElement('input');
                    tokenInput.type = 'hidden';
                    tokenInput.name = '_token';
                    tokenInput.value = token;
                    form.appendChild(tokenInput);
                    sortable.querySelectorAll('tr[data-id]').forEach(function (row) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'order[]';
                        input.value = row.dataset.id;
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        /* Порядок фотографий галереи: перетаскивание, порядок уходит вместе с формой */
        var gallery = document.getElementById('gallerySortable');
        if (gallery && window.jQuery && jQuery.fn.sortable) {
            jQuery(gallery).sortable({ items: '.js-gallery-item', handle: '.js-handle', axis: 'y' });
        }

        /* Предпросмотр выбранных файлов */
        document.querySelectorAll('.js-preview-input').forEach(function (input) {
            input.addEventListener('change', function () {
                var files = Array.prototype.slice.call(input.files || []);
                if (input.multiple) {
                    var list = input.parentElement.querySelector('.js-preview-list');
                    if (!list) return;
                    list.innerHTML = '';
                    files.forEach(function (file) {
                        var col = document.createElement('div');
                        col.className = 'col-6';
                        var img = document.createElement('img');
                        img.className = 'rounded';
                        img.src = URL.createObjectURL(file);
                        col.appendChild(img);
                        list.appendChild(col);
                    });
                } else {
                    var preview = input.parentElement.querySelector('.js-preview');
                    if (!preview) return;
                    if (files[0]) {
                        preview.src = URL.createObjectURL(files[0]);
                        preview.classList.remove('d-none');
                    } else {
                        preview.classList.add('d-none');
                    }
                }
            });
        });

        /* Строки «подпись и значение»: добавить и убрать (блоки «Данные», «Характеристики») */
        document.querySelectorAll('.js-pairs').forEach(function (block) {
            var list = block.querySelector('.js-pairs-list');
            block.querySelector('.js-add-pair').addEventListener('click', function () {
                var index = list.querySelectorAll('.js-pair').length;
                var name = block.dataset.name;
                var row = document.createElement('div');
                row.className = 'input-group mb-2 js-pair';
                row.innerHTML =
                    '<input type="text" class="form-control" name="' + name + '[' + index + '][label]" placeholder="' + (block.dataset.labelPlaceholder || '') + '" maxlength="100">' +
                    '<input type="text" class="form-control" name="' + name + '[' + index + '][value]" placeholder="' + (block.dataset.valuePlaceholder || '') + '" maxlength="255">' +
                    '<button type="button" class="btn btn-outline-danger js-remove-pair" title="Убрать строку"><i class="bx bx-x"></i></button>';
                list.appendChild(row);
                row.querySelector('input').focus();
            });
            list.addEventListener('click', function (event) {
                var button = event.target.closest('.js-remove-pair');
                if (button) button.closest('.js-pair').remove();
            });
        });

        /* Строки по шаблону <template> («Преимущества» на главной): добавить и убрать */
        document.querySelectorAll('.js-rows').forEach(function (block) {
            var list = block.querySelector('.js-rows-list');
            var template = block.querySelector('template');
            block.querySelector('.js-add-row').addEventListener('click', function () {
                var index = list.querySelectorAll('.js-row').length;
                list.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, index));
                var field = list.lastElementChild.querySelector('input[type="text"]');
                if (field) field.focus();
            });
            list.addEventListener('click', function (event) {
                var button = event.target.closest('.js-remove-row');
                if (button) button.closest('.js-row').remove();
            });
        });

        /*
         * Адрес ссылки: список страниц подставляет адрес в текстовое поле,
         * «Другой адрес...» показывает поле, чтобы вписать свой.
         */
        document.querySelectorAll('.js-link').forEach(function (block) {
            var select = block.querySelector('.js-link-select');
            var input = block.querySelector('.js-link-custom');
            select.addEventListener('change', function () {
                if (select.value === 'custom') {
                    input.classList.remove('d-none');
                    input.focus();
                } else {
                    input.value = select.value;
                    input.classList.add('d-none');
                }
            });
        });

        /*
         * Уменьшение фотографий в браузере перед отправкой. Большие снимки с
         * телефона (3..10 МБ) уменьшаются до 2000 px по длинной стороне и
         * пережимаются в JPEG; на сервер уходит лёгкий файл. PNG остаётся PNG.
         * Поворот по EXIF учитывается браузером при чтении (imageOrientation).
         * Если что-то не вышло, файл уходит как есть.
         */
        var MAX_SIDE = 2000;
        function shrinkFile(file) {
            if (!file.type || file.type.indexOf('image/') !== 0 || file.type === 'image/gif' || file.type === 'image/svg+xml') {
                return Promise.resolve(file);
            }
            var read = window.createImageBitmap
                ? createImageBitmap(file, { imageOrientation: 'from-image' })
                : Promise.reject(new Error('no createImageBitmap'));
            return read.then(function (bitmap) {
                var w = bitmap.width, h = bitmap.height;
                var scale = Math.min(1, MAX_SIDE / Math.max(w, h));
                if (scale === 1 && file.size < 1024 * 1024) {
                    bitmap.close && bitmap.close();
                    return file;
                }
                var canvas = document.createElement('canvas');
                canvas.width = Math.round(w * scale);
                canvas.height = Math.round(h * scale);
                canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
                bitmap.close && bitmap.close();
                var png = file.type === 'image/png';
                return new Promise(function (resolve) {
                    canvas.toBlob(function (blob) {
                        if (!blob || blob.size >= file.size) return resolve(file);
                        var name = file.name.replace(/\.[^.]+$/, '') + (png ? '.png' : '.jpg');
                        resolve(new File([blob], name, { type: blob.type, lastModified: Date.now() }));
                    }, png ? 'image/png' : 'image/jpeg', 0.85);
                });
            }).catch(function () { return file; });
        }

        var shrinking = 0;
        function setBusy(form, busy) {
            form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                button.disabled = busy;
            });
        }
        document.querySelectorAll('input[type="file"].js-shrink').forEach(function (input) {
            input.addEventListener('change', function () {
                var files = Array.prototype.slice.call(input.files || []);
                if (!files.length) return;
                var form = input.form;
                shrinking++;
                if (form) setBusy(form, true);
                Promise.all(files.map(shrinkFile)).then(function (result) {
                    var transfer = new DataTransfer();
                    result.forEach(function (f) { transfer.items.add(f); });
                    input.files = transfer.files;
                    var total = result.reduce(function (sum, f) { return sum + f.size; }, 0);
                    var note = input.parentElement.querySelector('.js-shrink-note');
                    if (!note) {
                        note = document.createElement('div');
                        note.className = 'form-text js-shrink-note';
                        input.insertAdjacentElement('afterend', note);
                    }
                    note.textContent = result.length + ' файл(ов), ' + (total / 1024 / 1024).toFixed(1) + ' МБ к отправке' +
                        (total > 7 * 1024 * 1024 ? '. Это много за один раз: сервер принимает до 8 МБ, выберите меньше файлов' : '');
                    note.classList.toggle('text-danger', total > 7 * 1024 * 1024);
                }).finally(function () {
                    shrinking--;
                    if (form && shrinking === 0) setBusy(form, false);
                });
            });
        });
    });
})();
