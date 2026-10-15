<?php

namespace Tests\Feature\Admin;

use App\Models\Apartment;
use App\Models\HomePage;
use App\Models\HomeRow;
use App\Models\Page;
use App\Models\Photo;
use App\Models\User;
use Database\Seeders\SiteContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/* Админка: «Главная сайта» и «Страницы списков» */
class HomeAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private function upload(string $name): UploadedFile
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $copy = tempnam(sys_get_temp_dir(), 'upl');
        copy(base_path('tests/Fixtures/photo.'.$extension), $copy);

        return new UploadedFile($copy, $name, mime_content_type($copy) ?: null, null, true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SiteContentSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_home_form_shows_texts_rows_and_objects(): void
    {
        $this->get('/admin/home-page')
            ->assertOk()
            ->assertSee('Пространство для жизни.')
            ->assertSee('Локальная экспертиза')
            ->assertSee('Высокий туристический спрос')
            ->assertSee('Покупка жилья для себя')
            ->assertSee('Жильё для сдачи')
            ->assertSee('Квартиры для собственного проживания')
            ->assertSee('Апартамент A10')
            ->assertSee('home/consultation.jpg')
            ->assertSee('Другой адрес...');
        $this->get('/admin')->assertOk()->assertSee('Главная сайта')->assertSee('Страницы списков');
    }

    public function test_home_texts_are_saved(): void
    {
        $home = HomePage::current();
        $photo = $home->photo(Photo::ROLE_CONSULTATION);

        $this->put('/admin/home-page', [
            'hero_title' => 'Новый заголовок.',
            'hero_title_accent' => 'Вторая строка.',
            'hero_primary_label' => 'Объекты',
            'hero_secondary_label' => 'Обсудить',
            'about_eyebrow' => '',
            'hero_side_items_text' => "Один\n\n  Два  \nТри\nЧетыре",
            'about_features' => [
                ['icon' => 'key', 'label' => 'Первое'],
                ['icon' => 'chart', 'label' => ''],
                ['icon' => 'shield', 'label' => 'Второе'],
            ],
            'objects_all_label' => 'Все объекты',
            'objects_all_href' => '/dlya-sebya',
            'consultation_title' => 'Подберём',
            'consultation_photo' => $this->upload('fon.png'),
            'photo_alt' => [$photo->id => 'старая подпись'],
        ])->assertRedirect('/admin/home-page')->assertSessionHas('success');

        $home = HomePage::current();
        $this->assertSame('Новый заголовок.', $home->hero_title);
        $this->assertSame(['Один', 'Два', 'Три', 'Четыре'], $home->hero_side_items);
        $this->assertSame([['icon' => 'key', 'label' => 'Первое'], ['icon' => 'shield', 'label' => 'Второе']], $home->about_features);
        $this->assertSame('/dlya-sebya', $home->objects_all_href);
        $this->assertNull($home->about_eyebrow, 'пустое поле сохраняется как null');
        $this->assertSame('Наши направления', $home->directions_label, 'поля, которых не было в форме, не трогаются');

        $new = $home->photo(Photo::ROLE_CONSULTATION);
        $this->assertNotSame($photo->id, $new->id);
        $this->assertStringStartsWith('home/consultation-', $new->path);
        $this->assertStringEndsWith('.png', $new->path);
        $this->assertTrue(Storage::disk('media')->exists($new->path));

        $api = $this->getJson('/api/home')->assertOk();
        $api->assertJsonPath('data.hero.title', 'Новый заголовок.');
        $api->assertJsonCount(4, 'data.hero.side_items');
        $api->assertJsonPath('data.about.features.1.label', 'Второе');
        $api->assertJsonPath('data.objects.all.href', '/dlya-sebya');
        $api->assertJsonPath('data.consultation.photo.url', config('app.url').'/media/'.$new->path);
    }

    public function test_home_form_errors(): void
    {
        $this->from('/admin/home-page')->put('/admin/home-page', [
            'hero_title' => '',
            'hero_primary_label' => 'x',
            'hero_secondary_label' => 'y',
            'objects_all_href' => 'dlya-sebya',
        ])->assertRedirect('/admin/home-page')->assertSessionHasErrors(['hero_title', 'objects_all_href']);
        $this->assertSame('Пространство для жизни.', HomePage::current()->hero_title);
    }

    public function test_scenario_row_is_added_edited_sorted_and_deleted(): void
    {
        $this->get('/admin/home-rows/create?kind=scenario')->assertOk()->assertSee('Текст ссылки')->assertSee('Другой кадр для компьютера');
        $this->get('/admin/home-rows/create?kind=direction')->assertOk()->assertDontSee('Текст ссылки');
        $this->get('/admin/home-rows/create')->assertSessionHasErrors('kind');

        /* Без фотографии и ссылки новая строка не сохраняется */
        $this->from('/admin/home-rows/create?kind=scenario')->post('/admin/home-rows', [
            'kind' => 'scenario', 'title' => 'Шестая', 'link_href' => 'nope',
        ])->assertSessionHasErrors(['photo', 'link_label', 'link_href']);

        $this->post('/admin/home-rows', [
            'kind' => 'scenario',
            'title' => 'Шестая строка',
            'text' => 'Текст шестой',
            'link_label' => 'Подробнее',
            'link_href' => '/apartamenty/a4',
            'photo' => $this->upload('six.jpg'),
            'alt_photo' => 'Подпись шестой',
        ])->assertRedirect('/admin/home-page#scenarios')->assertSessionHas('success');

        $row = HomeRow::query()->where('title', 'Шестая строка')->firstOrFail();
        $this->assertSame(6, $row->sort_order);
        $this->assertSame('home/scenario-'.$row->id, dirname($row->photo(Photo::ROLE_PHOTO)->path));
        $this->assertSame('Подпись шестой', $row->photo(Photo::ROLE_PHOTO)->alt);
        $this->getJson('/api/home')->assertJsonCount(6, 'data.scenarios')->assertJsonPath('data.scenarios.5.link.href', '/apartamenty/a4');

        $this->get("/admin/home-rows/{$row->id}/edit")->assertOk()->assertSee('Шестая строка')->assertSee('Апартамент A4');

        /* Правка с широким кадром, потом его удаление */
        $this->put("/admin/home-rows/{$row->id}", [
            'title' => 'Шестая строка', 'link_label' => 'Ещё', 'link_href' => 'https://example.com/x',
            'photo_wide' => $this->upload('wide.webp'),
        ])->assertRedirect('/admin/home-page#scenarios');
        $row->refresh()->load('photos');
        $this->assertSame('Ещё', $row->link_label);
        $this->assertSame('https://example.com/x', $row->link_href);
        $wide = $row->photo(Photo::ROLE_PHOTO_WIDE);
        $this->assertNotNull($wide);
        $this->getJson('/api/home')->assertJsonPath('data.scenarios.5.photo_wide.url', config('app.url').'/media/'.$wide->path);

        $this->put("/admin/home-rows/{$row->id}", [
            'title' => 'Шестая строка', 'link_label' => 'Ещё', 'link_href' => '/restoran',
            'delete_photo' => [$wide->id, $row->photo(Photo::ROLE_PHOTO)->id],
        ]);
        $row->refresh()->load('photos');
        $this->assertNull($row->photo(Photo::ROLE_PHOTO_WIDE));
        $this->assertNotNull($row->photo(Photo::ROLE_PHOTO), 'основную фотографию удалить нельзя');
        $this->assertFalse(Storage::disk('media')->exists($wide->path));

        /* Порядок: новая строка первой */
        $ids = HomeRow::query()->ofKind('scenario')->ordered()->pluck('id')->all();
        $this->post('/admin/home-rows/sort', ['order' => array_merge([$row->id], array_values(array_diff($ids, [$row->id])))])->assertRedirect();
        $this->getJson('/api/home')->assertJsonPath('data.scenarios.0.title', 'Шестая строка');

        $path = $row->photo(Photo::ROLE_PHOTO)->path;
        $this->delete("/admin/home-rows/{$row->id}")->assertRedirect('/admin/home-page#scenarios');
        $this->assertDatabaseMissing('home_rows', ['id' => $row->id]);
        $this->assertFalse(Storage::disk('media')->exists($path));
        $this->getJson('/api/home')->assertJsonCount(5, 'data.scenarios');
    }

    public function test_direction_card(): void
    {
        $this->post('/admin/home-rows', [
            'kind' => 'direction',
            'title' => 'Ресторан',
            'text' => 'Аренда помещения',
            'link_href' => '/restoran',
            'photo' => $this->upload('card.jpg'),
        ])->assertRedirect('/admin/home-page#directions');
        $this->getJson('/api/home')->assertJsonCount(5, 'data.directions.items')
            ->assertJsonPath('data.directions.items.4.href', '/restoran');
        $this->assertSame(5, HomeRow::query()->ofKind('direction')->max('sort_order'));
    }

    public function test_show_home_flag_in_apartment_form(): void
    {
        $a6 = Apartment::query()->where('slug', 'a6')->firstOrFail();
        $this->get("/admin/apartments/{$a6->id}/edit")->assertOk()->assertSee('На главной, в «Актуальных объектах»');

        $this->put("/admin/apartments/{$a6->id}", [
            'name' => $a6->name, 'slug' => 'a6', 'complex_id' => $a6->complex_id, 'status' => 'building',
            'show_living' => '1', 'show_renting' => '1', 'show_newbuilds' => '1',
        ])->assertRedirect();
        $this->assertFalse($a6->refresh()->show_home);
        $this->getJson('/api/home')->assertJsonCount(2, 'data.objects.items');
        $this->get('/admin/home-page')->assertOk()->assertDontSee('Апартамент A6</a>', false);
        $this->get('/admin/apartments')->assertOk()->assertSee('главная');
    }

    public function test_pages_index_and_edit(): void
    {
        $this->get('/admin/pages')->assertOk()->assertSee('Квартиры для сдачи в аренду')->assertSee('/novostroyki');
        $this->get('/admin/pages/renting/edit')->assertOk()->assertSee('Подберём квартиру для арендного дохода');
        $this->get('/admin/pages/nope/edit')->assertNotFound();

        $this->put('/admin/pages/renting', [
            'title' => 'Аренда',
            'lead' => 'Новый подзаголовок',
            'consultation_title' => '',
            'consultation_title_mobile' => 'На телефоне',
            'meta_title' => 'Аренда: ИКАН',
        ])->assertRedirect('/admin/pages')->assertSessionHas('success');
        $page = Page::forKey('renting');
        $this->assertSame('Аренда', $page->title);
        $this->assertNull($page->consultation_title);
        $this->getJson('/api/pages/renting')->assertJsonPath('data.title', 'Аренда')
            ->assertJsonPath('data.consultation.title_mobile', 'На телефоне');

        $this->from('/admin/pages/living/edit')->put('/admin/pages/living', ['title' => ''])->assertSessionHasErrors('title');
    }

    public function test_home_form_without_seeded_texts(): void
    {
        HomePage::query()->delete();
        $this->get('/admin/home-page')->assertOk()->assertSee('db:seed --class=HomeSeeder');
        $this->put('/admin/home-page', [
            'hero_title' => 'С нуля', 'hero_primary_label' => 'A', 'hero_secondary_label' => 'B',
        ])->assertRedirect('/admin/home-page');
        $this->assertSame(1, HomePage::query()->count());
        $this->getJson('/api/home')->assertJsonPath('data.hero.title', 'С нуля');
    }
}
