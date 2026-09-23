<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Chatbot AI — API key WAJIB di backend (.env), jangan di frontend.
    'openai' => [
        'key'   => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-nano'), // paling ringan & murah
    ],

    // Cloudflare Turnstile (anti-bot di login/daftar). Aman-nonaktif bila kosong.
    // site_key boleh publik (dipakai di frontend); secret HANYA di backend/.env.
    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret'   => env('TURNSTILE_SECRET_KEY'),
    ],

    // CoinMarketCap (ticker harga). Dibaca via config, bukan env() di controller.
    'cmc' => [
        'key' => env('CMC_API_KEY'),
    ],

];
