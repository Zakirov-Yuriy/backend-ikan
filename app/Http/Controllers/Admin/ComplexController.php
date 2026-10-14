<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ComplexRequest;
use App\Models\Complex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* Жилые комплексы: список с порядком, добавление, правка, удаление */
class ComplexController extends Controller
{
    public function index(): View
    {
        return view('admin.complexes.index', [
            'complexes' => Complex::query()->withCount('apartments')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.complexes.form', ['complex' => new Complex]);
    }

    public function store(ComplexRequest $request): RedirectResponse
    {
        $complex = new Complex($request->validated());
        $complex->sort_order = (int) Complex::query()->max('sort_order') + 1;
        $complex->save();

        return redirect()->route('admin.complexes.index')->with('success', "Жилой комплекс «{$complex->name}» добавлен.");
    }

    public function edit(Complex $complex): View
    {
        return view('admin.complexes.form', ['complex' => $complex]);
    }

    public function update(ComplexRequest $request, Complex $complex): RedirectResponse
    {
        $complex->update($request->validated());

        return redirect()->route('admin.complexes.index')->with('success', "Жилой комплекс «{$complex->name}» сохранён.");
    }

    public function destroy(Complex $complex): RedirectResponse
    {
        if ($complex->apartments()->exists()) {
            return back()->withErrors("В жилом комплексе «{$complex->name}» есть апартаменты. Сначала перенесите или удалите их.");
        }
        $complex->delete();

        return redirect()->route('admin.complexes.index')->with('success', "Жилой комплекс «{$complex->name}» удалён.");
    }

    /* Новый порядок вкладок: список id в нужном порядке */
    public function sort(Request $request): RedirectResponse
    {
        $ids = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']])['order'];
        foreach (array_values($ids) as $i => $id) {
            Complex::query()->whereKey($id)->update(['sort_order' => $i + 1]);
        }

        return back()->with('success', 'Порядок жилых комплексов сохранён.');
    }
}
