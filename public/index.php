<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$base = base_path();
if ($base !== '' && starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = trim($path, '/');
if (starts_with($path, 'index.php')) {
    $path = trim(substr($path, strlen('index.php')), '/');
}
$segments = $path === '' ? [] : explode('/', $path);

header('Content-Type: text/html; charset=utf-8');

if (($segments[0] ?? '') === 'admin') {
    require ROOT . '/app/admin.php';
    admin_dispatch(array_slice($segments, 1));
} else {
    require ROOT . '/app/site.php';
    site_dispatch($segments);
}
