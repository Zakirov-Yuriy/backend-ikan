# Бэкенд ИКАН: документация

Один проект Laravel на два дела: API для сайта (адреса `/api/...`) и админка для наполнения сайта (адреса `/admin/...`). Сайт (`ikan`, Next.js) берёт отсюда объекты, тексты, фото, телефоны и отправляет сюда заявки. Вики сайта в его репозитории, `docs/WIKI.md`.

- Репозиторий: https://github.com/Zakirov-Yuriy/backend-ikan
- Показ заказчику: https://backend-ikan.onrender.com (Render, бесплатный тариф), админка на `/admin`

## Стек

- PHP 8.4, Laravel 13.
- База SQLite, файл `database/database.sqlite`.
- Фото на диске `media`: папка `public/media`, адреса `{APP_URL}/media/...`.
- Админка на шаблоне Sneat (Bootstrap), как в админке ЛИДЛЕ; свои мелочи в `public/admin_front/assets/css/ikan.css`.
- Сессии, кэш, очередь в базе (`SESSION_DRIVER=database` и т. д.).

## Запуск на компьютере

Первый раз:

```
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan admin:user почта@пример.ру
php artisan serve
```

Бэкенд на http://localhost:8000, админка на http://localhost:8000/admin. `admin:user` спросит пароль; если скрытый ввод в терминале не работает, `--password=...`.

После установки нового архива обычно достаточно `php artisan migrate`. Если менялись тексты по умолчанию главной, ещё `php artisan db:seed --class=HomeSeeder` (правки из админки он не перезаписывает).

Проверки: `php artisan test` (PHPUnit), оформление кода: `vendor/bin/pint`.

## Начальное наполнение (seed)

- `SiteContentSeeder` (вызывается из `DatabaseSeeder`): четыре ЖК (L1 до L4), апартаменты A4, A6, A10, страница ресторана, фото из `database/seeders/media` копируются в `public/media`. Повторный запуск безопасен: записи ищутся по адресу (slug) и обновляются, лишние не удаляются.
- `HomeSeeder`: тексты и фото главной, вступления трёх списков. Повторный запуск не трогает то, что поменяли в админке; меняет только старые значения по умолчанию на новые.
- Полная пересборка базы: `php artisan migrate:fresh --seed` (всё из админки пропадёт).

## Команды

| Команда | Что делает |
|---|---|
| `php artisan admin:user {email} [--name=] [--password=]` | создать пользователя админки или сменить пароль |
| `php artisan demo:apartments l3 [--count=2]` | добавить тестовые апартаменты в ЖК (данные и фото собраны из A4, A6, A10, адрес `demo-l3-1` и т. д.) |
| `php artisan demo:apartments --remove` | удалить все тестовые апартаменты вместе с фото |

## Данные

| Модель | Таблица | Что хранит |
|---|---|---|
| `Complex` | `complexes` | жилой комплекс: адрес (slug, например `l1`), название, порядок |
| `Apartment` | `apartments` | апартамент: ЖК, стадия (строится, готов), галочки каталогов (для себя, аренда, новостройки, на главной), город, этаж, тип, площадь, цена, тексты карточки и страницы, характеристики, данные, SEO, порядок |
| `Restaurant` | `restaurants` | страница ресторана, одна запись |
| `Photo` | `photos` | фото любого объекта (апартамент, ресторан, строка главной, главная): роль, путь, подпись, размеры, порядок |
| `HomePage` | `home_pages` | тексты главной, одна запись |
| `HomeRow` | `home_rows` | строки главной: «Что мы предлагаем» (scenario) и «Наши направления» (direction) |
| `Page` | `pages` | вступления списков: `living`, `renting`, `newbuilds` |
| `Lead` | `leads` | заявки с сайта: имя, телефон, интерес, откуда (подвал или окно), страница, согласия (`consent_terms` и `consent_privacy` обязательные, `consent_ads` реклама по желанию), адрес посетителя, статус |
| `PageView` | `page_views` | просмотры страниц сайта для «Статистики»: день, обезличенный код посетителя на день, адрес, экран, апартамент, экран, с которого пришли по ссылке сайта, сайт-источник для первой страницы, устройство (телефон, планшет, компьютер); адресов посетителей и cookie нет |
| `Setting` | `settings` | настройки ключ и значение: телефоны, ссылки соцсетей, почта и Telegram для уведомлений |
| `User` | `users` | пользователи админки |

Роли фото (`Photo::ROLE_*`): `card` и `card_mobile` (карточка в списке на компьютере и телефоне), `gallery` (галерея страницы), `mobile` (главное фото страницы на телефоне), `photo`, `photo_wide` (строки главной), `consultation` (блок подбора на главной). Файлы раскладываются по папкам объекта (`PhotoStore`): `apartments/a4/card-k3j9x2.jpg`.

## API для сайта

Ответы в JSON, обёрнуты в `{ "data": ... }`. Адреса фото полные.

| Запрос | Что отдаёт |
|---|---|
| `GET /api/site` | `phone` (главный, `{label, href}`), `phones` (все телефоны), `socials` (`{max, telegram, vk}`, `null` если иконку не показывать) |
| `GET /api/home` | главная целиком: первый экран, строки, «О компании», направления, «Актуальные объекты» (до трёх), блок подбора, SEO |
| `GET /api/pages/{key}` | вступление списка: `living`, `renting`, `newbuilds` |
| `GET /api/complexes` | список ЖК по порядку |
| `GET /api/apartments` | карточки апартаментов; отбор `?catalog=living|renting|newbuilds`, `?status=building|ready`, `?complex=l1` |
| `GET /api/apartments/{slug}` | страница апартамента |
| `GET /api/restaurant` | страница ресторана |
| `POST /api/views` | просмотр страницы: `path`, `from` (предыдущая страница сайта), `referrer` (откуда пришли со стороны); роботы, неизвестные адреса и несуществующие апартаменты не считаются; ответ 204; не больше 120 в минуту с одного адреса |
| `POST /api/requests` | приём заявки; телефон: число цифр как в номерах страны (`giggsey/libphonenumber-for-php-lite`, `isPossibleNumber`, без кода страны номер российский); `consent_terms` и `consent_privacy` должны быть `true`, `consent_ads` необязательное; не больше 20 в минуту с одного адреса |

«Актуальные объекты» (`Apartment::homeObjects()`): первые три апартамента с галочкой «На главной» в порядке списка; если галочки нет ни у одного, первые три из списка.

## Заявки и уведомления

Заявка сохраняется в таблицу `leads` и видна в админке «Заявки» (колонка «Реклама»: согласен или нет). Уведомление (`LeadNotifier`) уходит:
- на почту из «Настроек» (адреса через запятую); как отправлять, задаётся в `.env` (`MAIL_MAILER=smtp` и данные ящика; сейчас `log`, письма пишутся в `storage/logs/laravel.log`);
- в Telegram, если в `.env` задан `TELEGRAM_BOT_TOKEN`, а в «Настройках» chat id (кнопка «Найти chat id» подставляет его).

- в Битрикс24 заказчика (`app/Services/Bitrix24.php`), если в «Настройках» вставлена ссылка входящего вебхука с правами «CRM» (или задан `BITRIX24_WEBHOOK` на сервере). В зависимости от настройки «Что создавать» создаётся лид (`crm.lead.add`, классический режим CRM) или контакт и сделка (`crm.contact.add`, `crm.deal.add`, простой режим без лидов). Поля: название «Заявка с сайта ИКАН: имя», имя, телефон, источник «Сайт» (`SOURCE_ID` WEB), в комментарии что интересует, форма и страница, согласие на рекламу; по желанию ответственный (`ASSIGNED_BY_ID`), воронка (`CATEGORY_ID`) и этап (`STAGE_ID`) сделки. Ошибка Битрикс24 не мешает сохранению заявки, пишется в лог.

В «Настройках» есть кнопки «Проверить Битрикс24» (связь и режим CRM, без создания записей) и «Отправить проверочное уведомление» (на почту, в Telegram и в Битрикс24, название записи начинается с «Проверка из админки»).

## Админка

Вход `/admin/login` («Запомнить меня» оставляет вход надолго). Разделы:

- **Главная**: сводка и новые заявки.
- **Статистика** (`SiteStats`, график ApexCharts 3.42 из `public/admin_front/assets/vendor/libs/apex-charts`, как в админке Lidza): период сегодня, 7 дней, 30 дней, текущий и прошлый месяц; посетители, просмотры, открытия апартаментов, заявки и доля посетителей с заявкой; график по дням; апартаменты (люди, просмотры, фото, заявки со страницы, доля с телефона, с каких экранов открывали карточку); экраны сайта; с каких экранов открывают карточки; откуда пришли (Яндекс, Google, Telegram, ВКонтакте, прямой заход и другие); устройства. Посетитель это код из адреса, браузера и даты, на следующий день другой, поэтому люди считаются по дням. Дни по Москве.
- **Жилые комплексы**: список, добавление, изменение, порядок перетаскиванием.
- **Апартаменты**: список с поиском и отбором по ЖК, форма со всеми полями и фото (загрузка, порядок перетаскиванием, удаление), галочки каталогов и «На главной», порядок перетаскиванием.
- **Ресторан**: страница ресторана и её фото.
- **Главная сайта**: все тексты и фото главной, строки «Что мы предлагаем» и «Наши направления», какие апартаменты в «Актуальных объектах».
- **Страницы списков**: заголовки и вступления трёх списков, SEO.
- **Заявки**: список, отбор новые и обработанные, согласие на рекламу, отметка «обработана», удаление.
- **Профиль** (меню по иконке в правом верхнем углу): «Мой профиль» (имя, почта для входа, даты) и «Настройки профиля» (смена имени, почты и пароля; почта и пароль меняются только с текущим паролем, новый пароль не короче 8 знаков). На Render при каждом запуске пользователь из `ADMIN_EMAIL` и `ADMIN_PASSWORD` создаётся заново, а база сбрасывается, поэтому смена там до следующего запуска.
- **Настройки**: главный и второй телефон, ссылки MAX, Telegram, ВКонтакте для иконок подвала, почта и Telegram для уведомлений, Битрикс24 (ссылка вебхука, лид или сделка, ответственный, воронка, этап).

## Настройки окружения

| Переменная | Зачем |
|---|---|
| `APP_KEY` | ключ шифрования; на хостинге задать постоянный, иначе при каждом запуске новый и вход в админку слетает |
| `APP_URL` | адрес бэкенда, из него адреса фото (на Render подставляется из `RENDER_EXTERNAL_URL`) |
| `SITE_URL` | адрес сайта, для ссылок «Открыть на сайте» в админке |
| `ADMIN_EMAIL`, `ADMIN_PASSWORD` | на Render: пользователь админки создаётся при запуске |
| `DEMO_APARTMENTS` | на Render: ЖК для тестовых апартаментов, например `L3` или `L3,L4` |
| `MAIL_*` | отправка писем о заявках |
| `TELEGRAM_BOT_TOKEN` | бот для уведомлений в Telegram |
| `BITRIX24_WEBHOOK`, `BITRIX24_ENTITY`, `BITRIX24_ASSIGNED` | запасные значения для Битрикс24, если в «Настройках» пусто: ссылка вебхука, `lead` или `deal`, номер ответственного; удобно на бесплатном Render, где база сбрасывается |

## Хостинг (показ заказчику)

Render, сервис из `Dockerfile` (PHP 8.4, Composer, `composer install --no-dev`). При запуске `docker/start.sh`: адрес, ключ, создание базы, `migrate --force`, `db:seed --force`, тестовые апартаменты, пользователь админки, `php artisan serve`. Health Check `/up`. Каждая отправка в `main` пересобирает сервис.

Важно: у бесплатного Render нет постоянного диска. База и фото стираются при каждой сборке, перезапуске и засыпании (через 15 минут без посещений). Поэтому на показе пропадают заявки, правки и фото из админки и слетает вход, а при запуске всё наполняется заново из seed. Для постоянной работы свой сервер, ниже. Прокси Render: в `bootstrap/app.php` доверие заголовкам `X-Forwarded-For` и `X-Forwarded-Proto`, иначе ссылки админки были бы http.

## Свой сервер (ik-ikan.ru)

Бэкенд и сайт (`ikan`, Next.js) на одном сервере: nginx, php-fpm 8.4, PostgreSQL. Снаружи виден только nginx с https. Бэкенд снаружи открыт по трём адресам на домене сайта, остальное закрыто:

| Адрес | Что | Снаружи |
|---|---|---|
| `ik-ikan.ru/admin` | админка, вход по почте и паролю | да, нужна заказчику |
| `ik-ikan.ru/admin_front/` | стили и скрипты админки (`public/admin_front`) | да, без них админка без оформления |
| `ik-ikan.ru/media/` | фото (`public/media`) | да |
| `/api/...` | API для сайта | нет: сайт ходит в него с этого же сервера по `127.0.0.1:8080` |
| всё остальное на `ik-ikan.ru` | сайт, Next.js на `127.0.0.1:3000` | да |

Сам сайт фото с бэкенда не требует: он отдаёт их со своего адреса `/media/...` и забирает у бэкенда по внутреннему адресу (`rewrites` в `next.config.ts` сайта), поэтому `APP_URL` на адреса фото на сайте не влияет.

### Установка бэкенда

Папка, например, `/var/www/back`. Пакет `laravel/boost` на сервере не нужен (это подсказки для редактора кода), на вопросы `boost:install` отвечать не надо: `composer install --no-dev` его не ставит.

```
cd /var/www/back
composer install --no-dev --optimize-autoloader --no-interaction
cp .env.example .env         # и поправить, см. ниже
php artisan key:generate     # APP_KEY; без него «No application encryption key has been specified»
php artisan migrate --force
php artisan db:seed --force  # тексты главной и списков, ЖК, объекты и фото со старого сайта; повторный запуск безопасен
php artisan admin:user почта@заказчика --password=пароль
php artisan config:cache && php artisan route:cache && php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache public/media
```

После каждого нового архива: `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan config:cache && php artisan route:cache && php artisan view:cache`.

`.env` на сервере (остальное как в `.env.example`):

```
APP_NAME=ИКАН
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ik-ikan.ru
SITE_URL=https://ik-ikan.ru

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ikan
DB_USERNAME=ikan
DB_PASSWORD=...

SESSION_SECURE_COOKIE=true
```

`APP_DEBUG=false` обязательно: с `true` страница ошибки показывает код и настройки всем посетителям. Для PostgreSQL нужно расширение `php8.4-pgsql`, для фото `php8.4-gd`; проверка: `composer check-platform-reqs`.

### nginx

```nginx
# Бэкенд для сайта: только с этого сервера. API, сюда ходит Next.js (BACKEND_URL=http://127.0.0.1:8080)
server {
    listen 127.0.0.1:8080;
    root /var/www/back/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }
}

# Сайт и админка, снаружи
server {
    listen 443 ssl;
    http2 on;
    server_name ik-ikan.ru www.ik-ikan.ru;
    # ssl_certificate и ssl_certificate_key от certbot

    # Фото в админке грузятся пачками, PHP принимает до 8 МБ за раз (post_max_size)
    client_max_body_size 10m;

    # Админка, её стили и скрипты, фото: в бэкенд
    location ~ ^/(admin|admin_front|media)(/|$) {
        root /var/www/back/public;
        try_files $uri /index.php?$query_string;
    }
    location = /index.php {
        root /var/www/back/public;
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param HTTPS on;
    }

    # Всё остальное: сайт
    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}

server {
    listen 80;
    server_name ik-ikan.ru www.ik-ikan.ru;
    return 301 https://$host$request_uri;
}
```

Как это работает: адреса `/admin...`, `/admin_front/...`, `/media/...` nginx ищет как файлы в `public` бэкенда (стили, фото), а чего нет, передаёт в `public/index.php`, то есть в Laravel с исходным адресом запроса. Остальное уходит в Next.js. Если админку хочется закрыть дополнительно, на `location ~ ^/(admin|admin_front|media)(/|$)` можно повесить `allow`/`deny` по адресам или `auth_basic`.

Сайт на сервере: `BACKEND_URL=http://127.0.0.1:8080` и `SITE_URL=https://ik-ikan.ru` в `.env.local`, `npm ci`, `npm run build`, `npm run start` (слушает только `127.0.0.1:3000`). Подробнее в вики сайта, раздел «Свой сервер».

## Правила в коде

- Комментарии, сообщения админки и ошибок на русском.
- Новые поля только миграциями, старые миграции не меняются.
- Тесты в `tests/Feature`: API, админка, профиль, заявки, Битрикс24, телефоны и соцсети, тестовые апартаменты.
