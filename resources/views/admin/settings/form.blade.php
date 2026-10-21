@extends('admin.layout')
@section('title', 'Настройки')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <h5 class="card-header">Настройки сайта</h5>
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf @method('PUT')
                        <div class="card-body">
                            @include('admin.notifications')
                            <div class="mb-4">
                                <label for="phone" class="form-label">Телефон на сайте*</label>
                                <input type="text" class="form-control" name="phone" id="phone" maxlength="30" required placeholder="+7 (991) 255-29-00" value="{{ old('phone', $phone) }}"/>
                                <div class="form-text">Главный телефон: на него звонят кнопки «Позвонить». В шапке, подвале, меню и текстах форм он стоит первым. Пишется так, как должен выглядеть на сайте.</div>
                            </div>
                            <div class="mb-4">
                                <label for="phone_2" class="form-label">Второй телефон</label>
                                <input type="text" class="form-control" name="phone_2" id="phone_2" maxlength="30" placeholder="+7 (991) 587-24-57" value="{{ old('phone_2', $phone2) }}"/>
                                <div class="form-text">Показывается рядом с главным везде, где телефон написан текстом. Если оставить пустым, на сайте будет один телефон.</div>
                            </div>

                            <div class="divider text-start"><div class="divider-text">Иконки в подвале сайта</div></div>
                            @foreach($socials as $item)
                                <div class="mb-3">
                                    <label for="{{ $item['key'] }}" class="form-label">{{ $item['label'] }}</label>
                                    <input type="url" class="form-control" name="{{ $item['key'] }}" id="{{ $item['key'] }}" maxlength="300" placeholder="{{ $item['default'] }}" value="{{ old($item['key'], $item['value']) }}"/>
                                </div>
                            @endforeach
                            <div class="form-text mb-4">Ссылка открывается в новой вкладке по нажатию на иконку. Если поле пустое, иконка на сайте не показывается.</div>

                            <div class="divider text-start"><div class="divider-text">Уведомления о заявках</div></div>
                            <div class="mb-4">
                                <label for="notify_emails" class="form-label">Почта для уведомлений</label>
                                <input type="text" class="form-control" name="notify_emails" id="notify_emails" maxlength="500" placeholder="zakcoyote@gmail.com, manager@ik-ikan.ru" value="{{ old('notify_emails', $notifyEmails) }}"/>
                                <div class="form-text">
                                    Один или несколько адресов через запятую. На каждый приходит письмо о новой заявке.
                                    @if($mailer === 'log')
                                        <span class="text-warning d-block mt-1">Сейчас отправка почты в режиме «log»: письма не уходят, а записываются в файл <code>storage/logs/laravel.log</code>. Настоящая отправка включается в <code>.env</code> (MAIL_MAILER=smtp и данные почтового ящика), это делается при переезде на хостинг.</span>
                                    @else
                                        <span class="d-block mt-1">Способ отправки: {{ $mailer }} (задан в .env).</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="telegram_chat_id" class="form-label">Telegram: chat id</label>
                                <input type="text" class="form-control" name="telegram_chat_id" id="telegram_chat_id" maxlength="50" placeholder="123456789" value="{{ old('telegram_chat_id', $telegramChatId) }}"/>
                                <div class="form-text">
                                    Сообщения шлёт бот, а бот умеет писать только тем, кто ему написал первым, и только по числовому chat id, не по имени.
                                    Порядок: 1) в Telegram у <strong>@BotFather</strong> создать бота и получить токен; 2) записать токен в <code>.env</code> строкой <code>TELEGRAM_BOT_TOKEN=...</code> и перезапустить сервер; 3) открыть своего бота и нажать «Start»; 4) здесь нажать «Найти chat id» и выбрать себя.
                                    @if(!$telegramToken)
                                        <span class="text-warning d-block mt-1">Токен бота в .env пока не задан.</span>
                                    @endif
                                </div>
                                @if(is_array($chats))
                                    <div class="mt-2">
                                        <span class="form-label d-block">Кто писал боту, нажмите, чтобы подставить:</span>
                                        @foreach($chats as $chat)
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1 mb-1 js-pick-chat" data-id="{{ $chat['id'] }}">{{ $chat['title'] }} <span class="text-muted">{{ $chat['id'] }}</span></button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="divider text-start"><div class="divider-text">Битрикс24: заявки в CRM</div></div>
                            <div class="mb-3">
                                <label for="bitrix_webhook" class="form-label">Ссылка входящего вебхука</label>
                                <input type="url" class="form-control" name="bitrix_webhook" id="bitrix_webhook" maxlength="500" placeholder="https://ваш-портал.bitrix24.ru/rest/1/abc123def456/" value="{{ old('bitrix_webhook', $bitrixWebhook) }}" autocomplete="off"/>
                                <div class="form-text">
                                    Каждая заявка с сайта будет создаваться в Битрикс24. Ссылку создаёт администратор Битрикс24: «Разработчикам», «Другое», «Входящий вебхук», в правах отметить «CRM», скопировать адрес вебхука сюда. Это секрет, как пароль: не пересылайте его в переписке. Если поле пустое, заявки в Битрикс24 не уходят.
                                    @if($bitrixFromEnv)
                                        <span class="d-block mt-1">Сейчас ссылка взята из настроек сервера (BITRIX24_WEBHOOK), поэтому поле пустое.</span>
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <span class="form-label d-block">Что создавать</span>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bitrix_entity" id="bitrix_lead" value="lead" @checked(old('bitrix_entity', $bitrixEntity) === 'lead')>
                                    <label class="form-check-label" for="bitrix_lead">Лид</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="bitrix_entity" id="bitrix_deal" value="deal" @checked(old('bitrix_entity', $bitrixEntity) === 'deal')>
                                    <label class="form-check-label" for="bitrix_deal">Сделка и контакт</label>
                                </div>
                                <div class="form-text">Лид, если в Битрикс24 классический режим CRM. Сделка и контакт, если простой режим, без лидов. Режим покажет кнопка «Проверить Битрикс24» ниже.</div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label for="bitrix_assigned" class="form-label">Ответственный, номер</label>
                                    <input type="number" class="form-control" name="bitrix_assigned" id="bitrix_assigned" min="1" placeholder="1" value="{{ old('bitrix_assigned', $bitrixAssigned) }}"/>
                                    <div class="form-text">Номер сотрудника в Битрикс24 (виден в адресе его профиля). Пусто: тот, кто создал вебхук.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="bitrix_category" class="form-label">Воронка сделок, номер</label>
                                    <input type="number" class="form-control" name="bitrix_category" id="bitrix_category" min="0" placeholder="0" value="{{ old('bitrix_category', $bitrixCategory) }}"/>
                                    <div class="form-text">Только для сделок. Пусто: общая воронка.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="bitrix_stage" class="form-label">Этап сделки, код</label>
                                    <input type="text" class="form-control" name="bitrix_stage" id="bitrix_stage" maxlength="50" placeholder="NEW" value="{{ old('bitrix_stage', $bitrixStage) }}"/>
                                    <div class="form-text">Только для сделок. Пусто: первый этап воронки.</div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-dark">Сохранить</button>
                        </div>
                    </form>
                    <div class="card-body border-top d-flex flex-wrap gap-2">
                        <form action="{{ route('admin.settings.chats') }}" method="POST">@csrf<button type="submit" class="btn btn-outline-secondary">Найти chat id</button></form>
                        <form action="{{ route('admin.settings.bitrix') }}" method="POST">@csrf<button type="submit" class="btn btn-outline-secondary">Проверить Битрикс24</button></form>
                        <form action="{{ route('admin.settings.test') }}" method="POST">@csrf<button type="submit" class="btn btn-outline-primary">Отправить проверочное уведомление</button></form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('click', function (event) {
            var button = event.target.closest('.js-pick-chat');
            if (button) document.getElementById('telegram_chat_id').value = button.dataset.id;
        });
    </script>
@endsection
