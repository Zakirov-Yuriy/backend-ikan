<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/* «Страницы списков»: заголовок, подзаголовок и подобное у трёх списков квартир */
class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::query()->get()->keyBy('key');

        return view('admin.pages.index', [
            'pages' => collect(Page::PAGES)->map(fn ($info, $key) => $pages[$key] ?? new Page(['key' => $key])),
        ]);
    }

    public function edit(string $key): View
    {
        abort_unless(isset(Page::PAGES[$key]), 404);

        return view('admin.pages.form', ['page' => Page::forKey($key) ?? new Page(['key' => $key])]);
    }

    public function update(PageRequest $request, string $key): RedirectResponse
    {
        abort_unless(isset(Page::PAGES[$key]), 404);
        $page = Page::forKey($key) ?? new Page(['key' => $key]);
        $page->fill($request->validated())->save();

        return redirect()->route('admin.pages.index')->with('success', "Страница «{$page->name()}» сохранена.");
    }
}
