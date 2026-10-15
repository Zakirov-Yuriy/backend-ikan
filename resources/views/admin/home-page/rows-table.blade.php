{{-- Таблица строк одного вида с перетаскиванием. $rows, $kind --}}
@php use App\Models\Photo; use App\Support\SitePages; @endphp
@if($rows->isEmpty())
    <div class="card-body pt-0"><p class="mb-0">Пока пусто.</p></div>
@else
    <div class="table-responsive text-nowrap">
        <table class="table">
            <thead class="table-light">
            <tr>
                <th><i class="bx bx-sort-alt-2"></i></th>
                <th>№</th>
                <th>Фото</th>
                <th>Заголовок</th>
                <th>Ссылка ведёт на</th>
                <th>Действия</th>
            </tr>
            </thead>
            <tbody class="table-border-bottom-0 js-sortable" data-sort-url="{{ route('admin.home-rows.sort') }}">
            @foreach($rows as $row)
                @php $photo = $row->photo(Photo::ROLE_PHOTO); @endphp
                <tr data-id="{{ $row->id }}">
                    <td class="js-handle" style="cursor: grab"><i class="bx bx-grid-vertical"></i></td>
                    <td class="text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                    <td>@if($photo)<img src="{{ $photo->url() }}" alt="" class="rounded" style="height: 40px; width: 56px; object-fit: cover">@endif</td>
                    <td>
                        <a href="{{ route('admin.home-rows.edit', $row) }}">{{ $row->title }}</a>
                        @if($row->link_label)<div class="small text-muted">{{ $row->link_label }}</div>@endif
                    </td>
                    <td>{{ SitePages::label($row->link_href) }}<div class="small text-muted">{{ $row->link_href }}</div></td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="bx bx-dots-vertical-rounded"></i></button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('admin.home-rows.edit', $row) }}"><i class="bx bx-edit-alt me-1"></i> Изменить</a>
                                <form action="{{ route('admin.home-rows.destroy', $row) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="dropdown-item js-confirm" data-confirm="Удалить «{{ $row->title }}» с главной?"><i class="bx bx-trash me-1"></i> Удалить</button>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
