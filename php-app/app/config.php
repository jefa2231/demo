<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

$rootDir = dirname(__DIR__);
load_env_file($rootDir . '/.env');

if (!isset($_ENV['DB_HOST'])) {
    load_env_file(dirname($rootDir) . '/.env');
}

$timezone = env_value('TIMEZONE', 'Asia/Jakarta') ?? 'Asia/Jakarta';
date_default_timezone_set($timezone);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = [
    'root_dir' => $rootDir,
    'public_dir' => $rootDir . '/public',
    'app_name' => env_value('APP_NAME', 'JeStore') ?? 'JeStore',
    'telegram_default' => env_value('TELEGRAM_DEFAULT', 't.me/jefa14') ?? 't.me/jefa14',
    'admin_cookie_name' => env_value('ADMIN_COOKIE_NAME', 'admin_session') ?? 'admin_session',
    'timezone' => $timezone,
    'db' => [
        'host' => env_value('DB_HOST', '127.0.0.1') ?? '127.0.0.1',
        'port' => (int) (env_value('DB_PORT', '3306') ?? '3306'),
        'name' => env_value('DB_NAME', 'lazastore') ?? 'lazastore',
        'user' => env_value('DB_USER', 'root') ?? 'root',
        'pass' => env_value('DB_PASS', '') ?? '',
    ],
];

