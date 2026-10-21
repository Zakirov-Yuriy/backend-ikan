<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/*
 * Просмотр страницы с сайта: POST /api/views (21.10). Сайт сообщает адрес
 * страницы, адрес предыдущей страницы сайта и сайт, с которого пришли.
 *
 * Без cookie и без хранения адреса посетителя. Чтобы считать людей, а не
 * только просмотры, из адреса, браузера и даты делается код, который
 * нельзя развернуть обратно; на следующий день у того же человека он другой.
 * Поэтому люди считаются по дням. Роботы поисковиков не считаются.
 */
class PageViewController extends Controller
{
    private const BOTS = '/bot|crawl|spider|slurp|preview|headless|lighthouse|pagespeed|facebookexternalhit|vercel|curl|wget|python|monitor|uptime/i';

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'from' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:500'],
        ]);

        $agent = (string) $request->userAgent();
        $screen = PageView::screenOf($data['path']);
        if ($agent === '' || preg_match(self::BOTS, $agent) || $screen === null) {
            return response()->noContent();
        }
        [$name, $slug] = $screen;

        $apartmentId = null;
        if ($slug !== null) {
            $apartmentId = Apartment::query()->where('slug', $slug)->value('id');
            if ($apartmentId === null) {
                return response()->noContent();
            }
        }

        $from = filled($data['from'] ?? null) ? PageView::screenOf($data['from']) : null;
        $host = null;
        if ($from === null && filled($data['referrer'] ?? null)) {
            $host = mb_strtolower((string) parse_url($data['referrer'], PHP_URL_HOST)) ?: null;
            $host = $host ? mb_substr(preg_replace('/^www\./', '', $host), 0, 100) : null;
        }

        $today = now('Europe/Moscow')->toDateString();
        PageView::query()->create([
            'day' => $today,
            'visitor' => substr(hash('sha256', config('app.key').'|'.$today.'|'.$request->ip().'|'.$agent), 0, 16),
            'path' => mb_substr('/'.ltrim(strtok($data['path'], '?#') ?: '', '/'), 0, 255),
            'screen' => $name,
            'apartment_id' => $apartmentId,
            'from_screen' => $from[0] ?? null,
            'referrer_host' => $host,
            'device' => PageView::deviceOf($agent),
        ]);

        return response()->noContent();
    }
}
