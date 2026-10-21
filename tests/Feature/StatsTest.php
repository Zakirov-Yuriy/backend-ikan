<?php

namespace Tests\Feature;

use App\Models\Apartment;
use App\Models\Complex;
use App\Models\Lead;
use App\Models\PageView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/* Статистика посещений: приём просмотров с сайта и раздел в админке (21.10) */
class StatsTest extends TestCase
{
    use RefreshDatabase;

    private const DESK = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/141.0 Safari/537.36';

    private const PHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1';

    private Apartment $a4;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $complex = Complex::query()->create(['name' => 'L1', 'slug' => 'l1']);
        $this->a4 = Apartment::query()->create(['slug' => 'a4', 'name' => 'Апартамент A4', 'complex_id' => $complex->id, 'status' => 'building']);
        Apartment::query()->create(['slug' => 'a6', 'name' => 'Апартамент A6', 'complex_id' => $complex->id, 'status' => 'building']);
    }

    private function track(array $data, string $agent = self::DESK, string $ip = '10.0.0.1')
    {
        return $this->withHeaders(['User-Agent' => $agent])->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/views', $data);
    }

    public function test_views_are_stored_without_ip_and_cookies(): void
    {
        $this->track(['path' => '/', 'referrer' => 'https://www.yandex.ru/search/?text=x'])->assertNoContent()->assertCookieMissing('visitor');
        $this->track(['path' => '/dlya-sebya', 'from' => '/']);
        $this->track(['path' => '/apartamenty/a4', 'from' => '/dlya-sebya', 'referrer' => 'https://google.com/']);
        $this->track(['path' => '/apartamenty/a4/foto?x=1', 'from' => '/apartamenty/a4'], self::PHONE, '10.0.0.2');

        $rows = PageView::query()->orderBy('id')->get();
        $this->assertCount(4, $rows);
        $this->assertSame(['home', 'living', 'apartment', 'apartment_photos'], $rows->pluck('screen')->all());
        $this->assertSame('yandex.ru', $rows[0]->referrer_host);
        $this->assertNull($rows[2]->referrer_host, 'переход внутри сайта: источник со стороны не пишется');
        $this->assertSame('living', $rows[2]->from_screen);
        $this->assertSame($this->a4->id, $rows[3]->apartment_id);
        $this->assertSame('/apartamenty/a4/foto', $rows[3]->path);
        $this->assertSame(['desktop', 'desktop', 'desktop', 'mobile'], $rows->pluck('device')->all());
        $this->assertSame(1, $rows->take(3)->pluck('visitor')->unique()->count());
        $this->assertNotSame($rows[0]->visitor, $rows[3]->visitor);
        $this->assertFalse(Schema::hasColumn('page_views', 'ip'));
        $this->assertStringNotContainsString('10.0.0.1', json_encode(PageView::query()->get()->toArray()));
    }

    public function test_bots_and_unknown_pages_are_not_counted(): void
    {
        $this->track(['path' => '/'], 'Mozilla/5.0 (compatible; YandexBot/3.0)')->assertNoContent();
        $this->track(['path' => '/'], 'Mozilla/5.0 HeadlessChrome/141.0');
        $this->track(['path' => '/'], '');
        $this->track(['path' => '/net-takoy']);
        $this->track(['path' => '/apartamenty/net-takogo']);
        $this->track([])->assertStatus(422);
        $this->assertSame(0, PageView::query()->count());
    }

    public function test_device_detection(): void
    {
        $this->assertSame('mobile', PageView::deviceOf(self::PHONE));
        $this->assertSame('mobile', PageView::deviceOf('Mozilla/5.0 (Linux; Android 14; Pixel 8) Chrome/141.0 Mobile Safari/537.36'));
        $this->assertSame('tablet', PageView::deviceOf('Mozilla/5.0 (Linux; Android 14; SM-X710) Chrome/141.0 Safari/537.36'));
        $this->assertSame('tablet', PageView::deviceOf('Mozilla/5.0 (iPad; CPU OS 12_0 like Mac OS X)'));
        $this->assertSame('desktop', PageView::deviceOf(self::DESK));
    }

    public function test_admin_stats_page(): void
    {
        $this->track(['path' => '/', 'referrer' => 'https://t.me/']);
        $this->track(['path' => '/apartamenty/a4', 'from' => '/']);
        $this->track(['path' => '/apartamenty/a4'], self::PHONE, '10.0.0.2');
        $this->track(['path' => '/dlya-sebya'], self::PHONE, '10.0.0.3');
        $this->track(['path' => '/apartamenty/a4', 'from' => '/dlya-sebya'], self::PHONE, '10.0.0.3');
        Lead::query()->create(['name' => 'Анна', 'phone' => '+7 999 000-11-22', 'source' => 'popup', 'page' => '/apartamenty/a4', 'consent_terms' => true, 'consent_privacy' => true]);

        $this->get('/admin/stats')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create());
        $this->get('/admin')->assertSee('Статистика');

        $response = $this->get('/admin/stats')->assertOk()
            ->assertSee('Статистика сайта')->assertSee('Текущий месяц')
            ->assertSee('Апартаменты: какие смотрят')->assertSee('С каких экранов открывают карточки апартаментов')
            ->assertSee('Telegram')->assertSee('Телефон')->assertSee('apexcharts.min.js');

        $apartments = $response->viewData('apartments');
        $a4 = $apartments->first();
        $this->assertSame('a4', $a4['apartment']->slug);
        $this->assertSame(3, $a4['visitors']);
        $this->assertSame(1, $a4['leads']);
        $this->assertEqualsWithDelta(66.7, $a4['mobile'], 0.01);
        $this->assertEqualsCanonicalizing(['Главная', 'Со стороны', 'Для себя'], $a4['from']->pluck('label')->all());

        $totals = $response->viewData('totals');
        $this->assertSame(3, $totals['visitors']);
        $this->assertSame(5, $totals['views']);
        $this->assertSame(1, $totals['leads']);

        $sources = $response->viewData('sources')->pluck('visitors', 'label');
        $this->assertSame(1, $sources['Telegram']);
        $this->assertSame(2, $sources['Прямой заход или закладка']);

        $devices = $response->viewData('devices')->pluck('visitors', 'label');
        $this->assertSame(2, $devices['Телефон']);
        $this->assertSame(1, $devices['Компьютер']);

        // прошлый месяц пустой, неизвестный период это текущий месяц
        $this->get('/admin/stats?period=prev')->assertOk()->assertSee('За этот период просмотров пока нет');
        $this->assertSame('month', $this->get('/admin/stats?period=xxx')->viewData('stats')->period);
        $this->get('/admin/stats?period=today')->assertOk();
        $this->get('/admin/stats?period=7')->assertOk();
    }

    public function test_source_labels(): void
    {
        $this->assertSame('Яндекс', PageView::sourceLabel('yandex.ru'));
        $this->assertSame('Яндекс', PageView::sourceLabel('ya.ru'));
        $this->assertSame('Google', PageView::sourceLabel('google.rs'));
        $this->assertSame('ВКонтакте', PageView::sourceLabel('vk.com'));
        $this->assertSame('example.org', PageView::sourceLabel('example.org'));
        $this->assertSame('Прямой заход или закладка', PageView::sourceLabel(null));
    }
}
