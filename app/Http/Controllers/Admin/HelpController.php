<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HelpBook;
use Illuminate\View\View;

/* Инструкция по админке: разделы из docs/ADMIN.md (02.11) */
class HelpController extends Controller
{
    public function show(?string $section = null): View
    {
        $sections = HelpBook::sections();
        $current = $section ? ($sections[$section] ?? abort(404)) : reset($sections);

        $slugs = array_keys($sections);
        $index = $current ? array_search($current['slug'], $slugs, true) : false;

        return view('admin.help.show', [
            'sections' => $sections,
            'current' => $current ?: null,
            'previous' => $index !== false && $index > 0 ? $sections[$slugs[$index - 1]] : null,
            'next' => $index !== false && $index < count($slugs) - 1 ? $sections[$slugs[$index + 1]] : null,
        ]);
    }
}
