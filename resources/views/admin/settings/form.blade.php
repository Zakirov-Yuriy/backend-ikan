@extends('admin.layout')
@section('title', 'Настройки')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-xl-8">
                <div class="card mb-4">
                    <h5 class="card-header">Уведомления о заявках</h5>
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf @method('PUT')
                        <div class="card-body">
                            @include('admin.notifications')
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
                            <button type="submit" class="btn btn-dark">Сохранить</button>
                        </div>
                    </form>
                    <div class="card-body border-top d-flex flex-wrap gap-2">
                        <form action="{{ route('admin.settings.chats') }}" method="POST">@csrf<button type="submit" class="btn btn-outline-secondary">Найти chat id</button></form>
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
