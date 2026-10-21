<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

/* Раздел «Статистика»: посещения сайта и просмотры апартаментов (21.10) */
class StatsController extends Controller
{
    public function index(Request $request): View
    {
        $stats = new SiteStats($request->query('period'));

        return view('admin.stats.index', [
            'stats' => $stats,
            'periods' => SiteStats::PERIODS,
            'totals' => $stats->totals(),
            'daily' => $stats->daily(),
            'apartments' => $stats->apartments(),
            'screens' => $stats->screens(),
            'cardSources' => $stats->cardSources(),
            'sources' => $stats->sources(),
            'devices' => $stats->devices(),
        ]);
    }
}
