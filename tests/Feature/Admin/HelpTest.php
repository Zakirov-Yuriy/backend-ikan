<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\HelpBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/* Инструкция в админке из docs/ADMIN.md, по разделам (02.11) */
class HelpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/help')->assertRedirect('/admin/login');
    }

    public function test_sections_come_from_the_manual(): void
    {
        $sections = HelpBook::sections();

        $this->assertArrayHasKey('screens', $sections);
        $this->assertArrayHasKey('apartments', $sections);
        $this->assertSame('Апартаменты', $sections['apartments']['title']);
        $this->assertSame('admin.apartments.index', $sections['apartments']['route']);
        $this->assertStringContainsString('<table class="table', $sections['screens']['html']);

        /* Чек-лист для проверки без метки: в админку не попадает и не прилипает к соседнему разделу */
        foreach ($sections as $section) {
            $this->assertStringNotContainsString('Чек-лист', $section['html'], $section['slug']);
        }
    }

    public function test_user_sees_help_with_menu_and_every_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/help')
            ->assertOk()
            ->assertSee('Инструкция')
            ->assertSee('Экраны сайта');

        foreach (HelpBook::sections() as $slug => $section) {
            $this->actingAs($user)->get('/admin/help/'.$slug)
                ->assertOk()
                ->assertSee($section['title']);
        }

        $this->actingAs($user)->get('/admin/help/apartments')->assertSee('Открыть раздел');
        $this->actingAs($user)->get('/admin/help/net-takogo')->assertNotFound();
    }

    public function test_menu_links_to_help(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/settings')
            ->assertOk()
            ->assertSee(route('admin.help'));
    }
}
