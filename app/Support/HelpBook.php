<?php

namespace App\Support;

use Illuminate\Support\Str;

/*
 * Инструкция для заказчика внутри админки (02.11). Текст один, docs/ADMIN.md:
 * там он читается как документ, здесь показывается по разделам. Раздел
 * начинается с метки <!-- help: slug --> перед заголовком «## ...». Разделы
 * без метки (чек-лист для проверки) в админку не попадают. Править текст
 * нужно в docs/ADMIN.md, админка подхватит сама.
 */
class HelpBook
{
    /* Куда ведёт кнопка «Открыть раздел» у раздела инструкции */
    private const ROUTES = [
        'login' => 'admin.profile.show',
        'dashboard' => 'admin.home',
        'complexes' => 'admin.complexes.index',
        'apartments' => 'admin.apartments.index',
        'restaurant' => 'admin.restaurant.edit',
        'home-page' => 'admin.home-page.edit',
        'pages' => 'admin.pages.index',
        'leads' => 'admin.leads.index',
        'stats' => 'admin.stats',
        'settings' => 'admin.settings.edit',
    ];

    public static function path(): string
    {
        return base_path('docs/ADMIN.md');
    }

    /**
     * Разделы в порядке файла.
     *
     * @return array<string, array{slug: string, title: string, html: string, route: ?string}>
     */
    public static function sections(): array
    {
        $text = is_file(self::path()) ? (string) file_get_contents(self::path()) : '';
        $parts = preg_split('/<!--\s*help:\s*([a-z0-9-]+)\s*-->/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        $sections = [];
        for ($i = 1; $i + 1 < count($parts); $i += 2) {
            $slug = $parts[$i];
            $section = self::section($slug, $parts[$i + 1]);
            if ($section) {
                $sections[$slug] = $section;
            }
        }

        return $sections;
    }

    /** @return array{slug: string, title: string, html: string, route: ?string}|null */
    private static function section(string $slug, string $body): ?array
    {
        $body = ltrim($body);
        if (! preg_match('/^##\s+(.+)$/mu', $body, $heading, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        /* Заголовок без номера: «5. Апартаменты» → «Апартаменты» */
        $title = trim(preg_replace('/^\d+\.\s*/u', '', $heading[1][0]) ?? $heading[1][0]);
        $content = substr($body, $heading[0][1] + strlen($heading[0][0]));

        /* Следующий заголовок «## ...» без метки это раздел не для админки: отрезаем */
        if (preg_match('/^##\s/mu', $content, $next, PREG_OFFSET_CAPTURE)) {
            $content = substr($content, 0, $next[0][1]);
        }

        return [
            'slug' => $slug,
            'title' => $title,
            'html' => self::html($content),
            'route' => self::ROUTES[$slug] ?? null,
        ];
    }

    /* Markdown в HTML (GitHub-разметка: таблицы, списки) с классами Bootstrap */
    private static function html(string $markdown): string
    {
        $html = (string) Str::markdown(trim($markdown), [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = str_replace(
            ['<table>', '</table>'],
            ['<div class="table-responsive"><table class="table table-bordered table-sm align-top">', '</table></div>'],
            $html,
        );

        return str_replace('<h3>', '<h3 class="h5 mt-4">', $html);
    }
}
