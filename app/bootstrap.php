<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));

$GLOBALS['CONFIG'] = require ROOT . '/app/config.default.php';
if (is_file(ROOT . '/config.php')) {
    $GLOBALS['CONFIG'] = array_replace($GLOBALS['CONFIG'], require ROOT . '/config.php');
}

date_default_timezone_set($GLOBALS['CONFIG']['timezone']);
error_reporting(E_ALL);
ini_set('display_errors', $GLOBALS['CONFIG']['debug'] ? '1' : '0');
mb_internal_encoding('UTF-8');

require ROOT . '/app/helpers.php';
require ROOT . '/app/db.php';
require ROOT . '/app/sanitize.php';
require ROOT . '/app/upload.php';
require ROOT . '/app/auth.php';

if (PHP_SAPI !== 'cli') {
    set_exception_handler(function (Throwable $e) {
        error_log((string)$e);
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo config('debug')
            ? '<pre>' . e((string)$e) . '</pre>'
            : '<!doctype html><meta charset="utf-8"><title>Ошибка</title><p style="font:16px sans-serif;padding:24px">Внутренняя ошибка сервера. Попробуйте позже.</p>';
    });
}
