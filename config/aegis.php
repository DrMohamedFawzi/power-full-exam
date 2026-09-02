<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    | Bounded contexts, in dependency order (lowest first). ModuleServiceProvider
    | walks this list to auto-load each module's routes and policies.
    */

    'modules' => [
        'Identity',
        'Academics',
        'Assessment',
        'Proctoring',
        'Overwatch',

        // Composition root for the role landing pages. Sits at the top of the
        // stack because a dashboard, by nature, reads across every context.
        // It owns no data and is read-only: Queries and views, nothing else.
        'Dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam runner
    |--------------------------------------------------------------------------
    */

    'exam' => [
        'heartbeat_interval_seconds' => (int) env('AEGIS_HEARTBEAT_INTERVAL', 15),
        'max_offline_seconds' => (int) env('AEGIS_MAX_OFFLINE_SECONDS', 120),
        'autosave_interval_seconds' => (int) env('AEGIS_AUTOSAVE_INTERVAL', 20),
        'grace_seconds' => (int) env('AEGIS_EXAM_GRACE_SECONDS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Proctoring
    |--------------------------------------------------------------------------
    | Penalty applied to the integrity index (0-100) per violation severity, and
    | the threshold below which a session is auto-flagged for review.
    */

    'proctoring' => [
        'penalties' => [
            'low' => 2,
            'medium' => 5,
            'high' => 12,
            'critical' => 25,
        ],
        'flag_threshold' => (int) env('AEGIS_FLAG_THRESHOLD', 60),
        'auto_submit_threshold' => (int) env('AEGIS_AUTO_SUBMIT_THRESHOLD', 25),
        'vision_enabled' => (bool) env('AEGIS_VISION_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Overwatch (WAF / rate limiting / bans)
    |--------------------------------------------------------------------------
    */

    'overwatch' => [
        'enabled' => (bool) env('AEGIS_OVERWATCH_ENABLED', true),
        'rate_limit_per_minute' => (int) env('AEGIS_RATE_LIMIT', 120),
        'auto_ban_threshold' => (int) env('AEGIS_AUTO_BAN_THRESHOLD', 5),
        'auto_ban_minutes' => (int) env('AEGIS_AUTO_BAN_MINUTES', 60),
        'trusted_ips' => array_filter(explode(',', (string) env('AEGIS_TRUSTED_IPS', '127.0.0.1'))),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI exam generation (Google Gemini)
    |--------------------------------------------------------------------------
    */

    'ai' => [
        'enabled' => (bool) env('AEGIS_AI_ENABLED', true),
        'provider' => env('AEGIS_AI_PROVIDER', 'gemini'),
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout_seconds' => (int) env('AEGIS_AI_TIMEOUT', 60),
        'max_questions' => (int) env('AEGIS_AI_MAX_QUESTIONS', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | QR login
    |--------------------------------------------------------------------------
    */

    'qr' => [
        'ttl_seconds' => (int) env('AEGIS_QR_TTL', 120),
    ],

];
