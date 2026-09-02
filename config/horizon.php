<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    */
    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    */
    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    */
    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    */
    'prefix' => env('HORIZON_PREFIX', Str::slug(env('APP_NAME', 'aegis'), '_').'_horizon:'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    | Alert when any queue's wait time exceeds these thresholds (seconds).
    */
    'waits' => [
        'redis:critical'     => 3,
        'redis:exam-answers' => 5,
        'redis:heartbeats'   => 10,
        'redis:proctoring'   => 10,
        'redis:default'      => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times (minutes)
    |--------------------------------------------------------------------------
    */
    'trim' => [
        'recent'        => 60,
        'pending'       => 60,
        'completed'     => 60,
        'recent_failed' => 10080, // 1 week
        'failed'        => 10080,
        'monitored'     => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Silenced Jobs
    |--------------------------------------------------------------------------
    */
    'silenced' => [],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    */
    'metrics' => [
        'trim_snapshots' => [
            'job'  => 24,
            'queue'=> 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    */
    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB) — per worker process
    |--------------------------------------------------------------------------
    */
    'memory_limit' => 256,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Five dedicated queues, each with isolated worker pools:
    |
    |  critical      → JWT refresh, session invalidation (high-priority, low-latency)
    |  exam-answers  → Student autosave upserts (burst: up to 50k/s during exam)
    |  heartbeats    → Heartbeat Redis buffer pushes (extreme throughput, tiny jobs)
    |  proctoring    → Vision/keystroke analysis (CPU-heavy, more memory)
    |  default       → Everything else (threats, emails, stats)
    |
    */
    'environments' => [

        'production' => [

            'critical' => [
                'connection'  => 'redis',
                'queue'       => ['critical'],
                'balance'     => 'auto',
                'minProcesses'=> 2,
                'maxProcesses'=> 20,
                'maxTime'     => 0,
                'maxJobs'     => 500,
                'memory'      => 256,
                'tries'       => 3,
                'timeout'     => 15,
                'nice'        => 0,
            ],

            'exam-answers' => [
                'connection'  => 'redis',
                'queue'       => ['exam-answers'],
                'balance'     => 'auto',
                'autoScalingStrategy' => 'size',
                'minProcesses'=> 10,
                'maxProcesses'=> 200,
                'maxTime'     => 0,
                'maxJobs'     => 1000,
                'memory'      => 128,
                'tries'       => 5,
                'timeout'     => 10,
                'nice'        => 0,
            ],

            'heartbeats' => [
                'connection'  => 'redis',
                'queue'       => ['heartbeats'],
                'balance'     => 'auto',
                'autoScalingStrategy' => 'size',
                'minProcesses'=> 5,
                'maxProcesses'=> 100,
                'maxTime'     => 0,
                'maxJobs'     => 2000,
                'memory'      => 64,
                'tries'       => 3,
                'timeout'     => 5,
                'nice'        => 5,
            ],

            'proctoring' => [
                'connection'  => 'redis',
                'queue'       => ['proctoring'],
                'balance'     => 'auto',
                'autoScalingStrategy' => 'size',
                'minProcesses'=> 5,
                'maxProcesses'=> 50,
                'maxTime'     => 0,
                'maxJobs'     => 200,
                'memory'      => 256,
                'tries'       => 3,
                'timeout'     => 60,
                'nice'        => 10,
            ],

            'default' => [
                'connection'  => 'redis',
                'queue'       => ['default'],
                'balance'     => 'simple',
                'minProcesses'=> 2,
                'maxProcesses'=> 20,
                'maxTime'     => 0,
                'maxJobs'     => 500,
                'memory'      => 256,
                'tries'       => 3,
                'timeout'     => 90,
                'nice'        => 15,
            ],

        ],

        'local' => [

            'local-worker' => [
                'connection'  => 'redis',
                'queue'       => ['critical', 'exam-answers', 'heartbeats', 'proctoring', 'default'],
                'balance'     => 'false',
                'processes'   => 4,
                'tries'       => 1,
                'timeout'     => 60,
            ],

        ],

    ],

];
