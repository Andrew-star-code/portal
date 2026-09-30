<?php
declare(strict_types=1);

const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
const MATERIAL_TYPES = ['application/pdf' => 'pdf', 'video/mp4' => 'mp4', 'video/webm' => 'webm'];
const LOGO_TYPES = IMAGE_TYPES + ['image/svg+xml' => 'svg'];
const FAVICON_TYPES = ['image/png' => 'png', 'image/svg+xml' => 'svg', 'image/vnd.microsoft.icon' => 'ico', 'image/x-icon' => 'ico'];

/**
 * Сохраняет загруженный файл в public/uploads/[$subdir/]ГГГГ/ММ/ со случайным именем.
 * $types — разрешённые MIME-типы (определяются по содержимому) => расширение.
 * Возвращает путь относительно корня сайта, например 'uploads/files/2025/05/ab12cd.pdf'.
 */
function save_uploaded_file(array $file, array $types, int $maxMb, string $subdir = ''): string
{
    $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('Файл больше лимита сервера (' . upload_limit_mb($maxMb) . ' МБ).');
    }
    if ($err === UPLOAD_ERR_PARTIAL) {
        throw new RuntimeException('Файл загрузился не полностью, попробуйте ещё раз.');
    }
    if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('Файл не был загружен.');
    }
    if ($file['size'] > $maxMb * 1024 * 1024) {
        throw new RuntimeException("Файл больше $maxMb МБ.");
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    // SVG finfo определяет по-разному (text/xml, text/plain…) — распознаём сами.
    if (in_array('svg', $types, true) && preg_match('~\.svg$~i', (string)($file['name'] ?? ''))) {
        $head = (string)file_get_contents($file['tmp_name'], false, null, 0, 2 * 1024 * 1024);
        if (preg_match('~<svg[\s>]~i', $head)) {
            if (preg_match('~<script|<foreignObject|\bon[a-z]+\s*=|javascript:~i', $head)) {
                throw new RuntimeException('SVG содержит скрипты или обработчики событий — пересохраните его без них.');
            }
            $mime = 'image/svg+xml';
        }
    }
    if (!isset($types[$mime])) {
        throw new RuntimeException('Недопустимый тип файла. Разрешены: ' . strtoupper(implode(', ', array_unique($types))) . '.');
    }
    if (str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml' && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Файл повреждён или не является изображением.');
    }

    $rel = 'uploads/' . ($subdir !== '' ? $subdir . '/' : '') . date('Y/m');
    $dir = rtrim(config('uploads_dir'), '/\\') . '/' . substr($rel, strlen('uploads/'));
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Не удалось создать каталог для загрузок.');
    }
    $name = bin2hex(random_bytes(8)) . '.' . $types[$mime];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
        throw new RuntimeException('Не удалось сохранить файл.');
    }
    @chmod("$dir/$name", 0644);
    return "$rel/$name";
}

function save_uploaded_image(array $file): string
{
    return save_uploaded_file($file, IMAGE_TYPES, (int)config('upload_max_mb'));
}

/** Фактический лимит загрузки в МБ с учётом настроек PHP. */
function upload_limit_mb(int $appMax): int
{
    $toMb = function (string $v): int {
        $v = trim($v);
        $n = (float)$v;
        return (int)match (strtolower(substr($v, -1))) {
            'g' => $n * 1024,
            'm' => $n,
            'k' => $n / 1024,
            default => $n / 1024 / 1024,
        };
    };
    $limits = [$appMax, $toMb((string)ini_get('upload_max_filesize'))];
    $post = $toMb((string)ini_get('post_max_size'));
    if ($post > 0) {
        $limits[] = $post;
    }
    return max(1, min($limits));
}

/** Удаляет ранее загруженный файл (только внутри каталога загрузок). */
function delete_upload(?string $rel): void
{
    if (!$rel || !preg_match('~^uploads/(files/)?\d{4}/\d{2}/[a-f0-9]{16}\.(jpg|png|webp|gif|svg|ico|pdf|mp4|webm)$~', $rel)) {
        return;
    }
    $path = rtrim(config('uploads_dir'), '/\\') . '/' . substr($rel, strlen('uploads/'));
    if (is_file($path)) {
        @unlink($path);
    }
}

function human_size(int $bytes): string
{
    if ($bytes >= 1024 ** 3) {
        return number_format($bytes / 1024 ** 3, 1, ',', ' ') . ' ГБ';
    }
    if ($bytes >= 1024 ** 2) {
        return number_format($bytes / 1024 ** 2, 1, ',', ' ') . ' МБ';
    }
    return max(1, (int)round($bytes / 1024)) . ' КБ';
}
