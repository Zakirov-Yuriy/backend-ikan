<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /* Бот Telegram для уведомлений о заявках: токен от @BotFather в .env, TELEGRAM_BOT_TOKEN */
    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
    ],

    /*
     * Битрикс24: запасные значения, если в «Настройках» админки пусто.
     * Удобно на хостинге, где база сбрасывается (бесплатный Render).
     */
    'bitrix24' => [
        'webhook' => env('BITRIX24_WEBHOOK'),
        'entity' => env('BITRIX24_ENTITY', 'lead'),
        'assigned' => env('BITRIX24_ASSIGNED'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
