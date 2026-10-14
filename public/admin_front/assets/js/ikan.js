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
        var sortable = document.getElementById('sortable');
        if (sortable && window.jQuery && jQuery.fn.sortable) {
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
        }

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

        /* Строки блока «Данные»: добавить и убрать */
        var items = document.getElementById('dataItems');
        var add = document.getElementById('addDataItem');
        if (items && add) {
            add.addEventListener('click', function () {
                var index = items.querySelectorAll('.js-data-item').length;
                var row = document.createElement('div');
                row.className = 'input-group mb-2 js-data-item';
                row.innerHTML =
                    '<input type="text" class="form-control" name="data_items[' + index + '][label]" placeholder="Подпись" maxlength="100">' +
                    '<input type="text" class="form-control" name="data_items[' + index + '][value]" placeholder="Значение" maxlength="255">' +
                    '<button type="button" class="btn btn-outline-danger js-remove-item" title="Убрать строку"><i class="bx bx-x"></i></button>';
                items.appendChild(row);
                row.querySelector('input').focus();
            });
            items.addEventListener('click', function (event) {
                var button = event.target.closest('.js-remove-item');
                if (button) button.closest('.js-data-item').remove();
            });
        }
    });
})();
