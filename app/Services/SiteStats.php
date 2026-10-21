<?php

namespace App\Services;

use App\Models\Apartment;
use App\Models\Lead;
use App\Models\PageView;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/*
 * Цифры для раздела «Статистика» (21.10) за выбранный период: по дням,
 * апартаменты, экраны, с каких экранов открывают карточки, откуда пришли,
 * устройства, заявки. Дни считаются по Москве.
 *
 * Посетитель это обезличенный код на один день, поэтому «посетители» за
 * период это сумма посетителей по дням: человек, который заходил в
 * понедельник и во вторник, считается два раза.
 */
class SiteStats
{
    public const TZ = 'Europe/Moscow';

    public const PERIODS = [
        'today' => 'Сегодня',
        '7' => '7 дней',
        '30' => '30 дней',
        'month' => 'Текущий месяц',
        'prev' => 'Прошлый месяц',
    ];

    public readonly CarbonImmutable $from;

    public readonly CarbonImmutable $to;

    public readonly string $period;

    private Collection $views;

    private Collection $leads;

    public function __construct(?string $period)
    {
        $this->period = array_key_exists((string) $period, self::PERIODS) ? (string) $period : 'month';
        $today = CarbonImmutable::now(self::TZ)->startOfDay();
        [$this->from, $this->to] = match ($this->period) {
            'today' => [$today, $today],
            '7' => [$today->subDays(6), $today],
            '30' => [$today->subDays(29), $today],
            'prev' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()->startOfDay()],
            default => [$today->startOfMonth(), $today->endOfMonth()->startOfDay()],
        };

        $this->views = PageView::query()
            ->whereBetween('day', [$this->from->toDateString(), $this->to->toDateString()])
            ->get(['day', 'visitor', 'screen', 'apartment_id', 'from_screen', 'referrer_host', 'device']);

        $this->leads = Lead::query()
            ->whereBetween('created_at', [$this->from->setTimezone('UTC'), $this->to->endOfDay()->setTimezone('UTC')])
            ->get(['created_at', 'page'])
            ->map(fn (Lead $lead) => [
                'day' => $lead->created_at->setTimezone(self::TZ)->toDateString(),
                'page' => (string) $lead->page,
                'screen' => PageView::screenOf((string) $lead->page),
            ]);
    }

    /** Плитки над графиком */
    public function totals(): array
    {
        $visitors = $this->views->pluck('visitor')->unique()->count();
        $leads = $this->leads->count();

        return [
            'visitors' => $visitors,
            'views' => $this->views->count(),
            'apartmentViews' => $this->views->where('screen', 'apartment')->count(),
            'leads' => $leads,
            'conversion' => $this->percent($leads, $visitors),
        ];
    }

    /** По дням: подписи, посетители, просмотры, заявки (дни после сегодня пустые) */
    public function daily(): array
    {
        $today = CarbonImmutable::now(self::TZ)->toDateString();
        $byDay = $this->views->groupBy(fn (PageView $v) => $v->day->toDateString());
        $leadsByDay = $this->leads->countBy('day');
        $out = ['days' => [], 'visitors' => [], 'views' => [], 'leads' => []];
        for ($d = $this->from; $d <= $this->to; $d = $d->addDay()) {
            $key = $d->toDateString();
            $future = $key > $today;
            $out['days'][] = $key;
            $out['visitors'][] = $future ? null : ($byDay->get($key)?->pluck('visitor')->unique()->count() ?? 0);
            $out['views'][] = $future ? null : ($byDay->get($key)?->count() ?? 0);
            $out['leads'][] = $future ? null : ($leadsByDay->get($key) ?? 0);
        }

        return $out;
    }

    /**
     * Апартаменты по просмотрам страницы: люди, просмотры, фото, заявки
     * со страницы апартамента, с каких экранов открывали карточку.
     */
    public function apartments(): Collection
    {
        $pages = $this->views->where('screen', 'apartment')->groupBy('apartment_id');
        $photos = $this->views->where('screen', 'apartment_photos')->countBy('apartment_id');

        return Apartment::query()->with('complex')->ordered()->get()
            ->map(function (Apartment $apartment) use ($pages, $photos) {
                $views = $pages->get($apartment->id, collect());
                $visitors = $views->pluck('visitor')->unique()->count();
                $leads = $this->leads->filter(fn ($l) => ($l['screen'][1] ?? null) === $apartment->slug)->count();
                $from = $views->countBy(fn ($v) => $v->from_screen ?? '')->sortDesc()
                    ->map(fn ($count, $screen) => ['label' => $screen === '' ? 'Со стороны' : PageView::screenLabel($screen), 'count' => $count])
                    ->values();

                return [
                    'apartment' => $apartment,
                    'views' => $views->count(),
                    'visitors' => $visitors,
                    'photos' => $photos->get($apartment->id, 0),
                    'leads' => $leads,
                    'conversion' => $this->percent($leads, $visitors),
                    'mobile' => $this->mobileShare($views),
                    'from' => $from,
                ];
            })
            ->sortByDesc(fn ($row) => [$row['visitors'], $row['views']])
            ->values();
    }

    /** Экраны сайта: просмотры, люди, заявки с экрана */
    public function screens(): Collection
    {
        $byScreen = $this->views->groupBy('screen');
        $leads = $this->leads->countBy(fn ($l) => $l['screen'][0] ?? '');

        return collect(PageView::SCREENS)
            ->map(function ($label, $screen) use ($byScreen, $leads) {
                $views = $byScreen->get($screen, collect());
                $visitors = $views->pluck('visitor')->unique()->count();

                return [
                    'label' => $label,
                    'views' => $views->count(),
                    'visitors' => $visitors,
                    'leads' => $leads->get($screen, 0),
                    'conversion' => $this->percent($leads->get($screen, 0), $visitors),
                    'mobile' => $this->mobileShare($views),
                ];
            })
            ->sortByDesc('views')
            ->values();
    }

    /** С каких экранов открывают карточки апартаментов (переход на страницу апартамента) */
    public function cardSources(): Collection
    {
        $opens = $this->views->where('screen', 'apartment');
        $total = $opens->count();

        return $opens->countBy(fn ($v) => $v->from_screen ?? '')
            ->sortDesc()
            ->map(fn ($count, $screen) => [
                'label' => $screen === '' ? 'Сразу на страницу апартамента (по ссылке, из поиска)' : PageView::screenLabel($screen),
                'count' => $count,
                'share' => $this->percent($count, $total),
            ])
            ->values();
    }

    /** Откуда пришли на сайт: люди по первой странице захода */
    public function sources(): Collection
    {
        $entries = $this->views->whereNull('from_screen');
        $visitors = $entries->pluck('visitor')->unique()->count();

        return $entries->groupBy(fn ($v) => PageView::sourceLabel($v->referrer_host))
            ->map(fn ($rows, $label) => ['label' => $label, 'visitors' => $rows->pluck('visitor')->unique()->count()])
            ->map(fn ($row) => $row + ['share' => $this->percent($row['visitors'], $visitors)])
            ->sortByDesc('visitors')
            ->values();
    }

    /** Телефон, планшет, компьютер: люди и доля */
    public function devices(): Collection
    {
        $byDevice = $this->views->groupBy('device')->map(fn ($rows) => $rows->pluck('visitor')->unique()->count());
        $total = $byDevice->sum();

        return collect(PageView::DEVICES)->map(fn ($label, $device) => [
            'label' => $label,
            'visitors' => $byDevice->get($device, 0),
            'share' => $this->percent($byDevice->get($device, 0), $total),
        ])->values();
    }

    /* Доля людей с телефона среди просмотров */
    private function mobileShare(Collection $views): ?float
    {
        $visitors = $views->pluck('visitor')->unique();

        return $this->percent($views->where('device', PageView::DEVICE_MOBILE)->pluck('visitor')->unique()->count(), $visitors->count());
    }

    public function periodText(): string
    {
        return $this->from->equalTo($this->to)
            ? $this->from->format('d.m.Y')
            : $this->from->format('d.m.Y').' по '.$this->to->format('d.m.Y');
    }

    private function percent(int $part, int $whole): ?float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : null;
    }
}
