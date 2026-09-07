<?php
/**
 * راه‌اندازی اولیه‌ی برنامه: بارگذاری تنظیمات، توابع کمکی، دیتابیس و احراز هویت
 */
declare(strict_types=1);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tehran');
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') ? '1' : '0');

define('APP_ROOT', dirname(__DIR__));

$config = require __DIR__ . '/config.php';
$GLOBALS['config'] = $config;

/*
 * محاسبه‌ی BASE_URL (مسیر وب ریشه‌ی پروژه) تا سایت هم در ریشه‌ی دامنه و هم در زیرپوشه کار کند.
 */
(function () {
    $scriptDir   = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $appRoot     = str_replace('\\', '/', realpath(APP_ROOT) ?: APP_ROOT);
    $scriptFsDir = str_replace('\\', '/', dirname(realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: ''));
    $rel   = trim(substr($scriptFsDir, strlen($appRoot)), '/');
    $depth = $rel === '' ? 0 : substr_count($rel, '/') + 1;
    $base  = $scriptDir;
    for ($i = 0; $i < $depth; $i++) {
        $base = dirname($base);
    }
    $base = str_replace('\\', '/', $base);
    define('BASE_URL', rtrim($base, '/'));
})();

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';

db_init($config);
