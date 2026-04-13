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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'evolution' => [
        'base_url' => env('EVOLUTION_API_BASE_URL'),
        'api_key' => env('EVOLUTION_API_KEY'),
    ],

    'treinamento_id' => env('FORMED_SERVICO_TREINAMENTO_ID', 8),
    'esocial_id'     => env('FORMED_SERVICO_ESOCIAL_ID'),
    'exame_id'       => env('FORMED_SERVICO_EXAME_ID'),
    'aso_id'         => env('FORMED_SERVICO_ASO_ID'),
    'upload_limits'  => [
        'default_mb' => (int) env('FORMED_UPLOAD_MAX_MB_DEFAULT', 10),
        'pgr_mb' => (int) env('FORMED_UPLOAD_MAX_MB_PGR', 100),
        'pcmso_mb' => (int) env('FORMED_UPLOAD_MAX_MB_PCMSO', 100),
    ],

];
