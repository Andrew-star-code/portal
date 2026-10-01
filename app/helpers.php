<?php
declare(strict_types=1);

function config(string $key)
{
    return $GLOBALS['CONFIG'][$key] ?? null;
}

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Замены str_starts_with()/str_ends_with() из PHP 8 — код работает и на PHP 7.
function starts_with(string $haystack, string $needle): bool
{
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}

function ends_with(string $haystack, string $needle): bool
{
    return $needle === '' || substr($haystack, -strlen($needle)) === $needle;
}

/** URL-префикс сайта без завершающего слэша ('' для корня домена). */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $cfg = config('base_url');
    if ($cfg !== null) {
        return $base = rtrim((string)$cfg, '/');
    }
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = preg_replace('~/public$~', '', $dir);
    return $base = rtrim($dir, '/');
}

function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** Ссылка из контента: внешние http(s) оставляем, внутренние пути дополняем префиксом. */
function link_href(string $link): string
{
    if ($link === '' || preg_match('~^(https?:)?//~i', $link) || preg_match('~^(mailto|tel):~i', $link) || $link[0] === '#') {
        return $link;
    }
    return url($link);
}

function asset(string $path): string
{
    $file = ROOT . '/public/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url($path) . $v;
}

function redirect(string $path, int $code = 302)
{
    header('Location: ' . (preg_match('~^https?://~', $path) ? $path : url($path)), true, $code);
    exit;
}

function view(string $name, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require ROOT . '/app/views/' . $name . '.php';
    return (string)ob_get_clean();
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function fmt_date(string $date): string
{
    $t = strtotime($date);
    return $t ? date('d.m.Y', $t) : '';
}

/** plural(5, 'инструкция', 'инструкции', 'инструкций') → 'инструкций' */
function plural(int $n, string $one, string $few, string $many): string
{
    $n = abs($n) % 100;
    if ($n >= 11 && $n <= 14) {
        return $many;
    }
    $n %= 10;
    if ($n === 1) {
        return $one;
    }
    if ($n >= 2 && $n <= 4) {
        return $few;
    }
    return $many;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function slugify(string $s): string
{
    static $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
        'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
        'х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
    ];
    $s = strtr(mb_strtolower($s), $map);
    $s = preg_replace('~[^a-z0-9]+~', '-', $s);
    $s = trim((string)$s, '-');
    return mb_substr($s, 0, 80) ?: 'item';
}

function setting(string $key, string $default = ''): string
{
    static $all = null;
    if ($all === null) {
        $all = db()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return $all[$key] ?? $default;
}

/** Стандартный значок логотипа — чёрно-красный ромб из макета. */
const LOGO_DIAMOND_SVG = '<svg viewBox="0 0 30 18" aria-hidden="true"><path d="M9 1l8 8-8 8-8-8z" fill="var(--fg)"/><path d="M21 5l4 4-4 4-4-4z" fill="var(--accent)"/></svg>';

/**
 * Логотип в шапке по настройкам:
 *  - «Картинка целиком» (logo_mode = image);
 *  - конструктор: текст слева + значок (ромб / своя картинка / без значка) + текст справа.
 */
function logo_html(): string
{
    $home = e(url('/'));
    if (setting('logo_mode') === 'image' && setting('logo_image') !== '') {
        return '<a class="logo logo--img" href="' . $home . '" aria-label="На главную"><img src="' . e(url(setting('logo_image'))) . '" alt="' . e(setting('site_name')) . '"></a>';
    }
    $iconMode = setting('logo_icon_mode', 'diamond');
    if ($iconMode === 'none') {
        $icon = '';
    } elseif ($iconMode === 'image' && setting('logo_icon') !== '') {
        $icon = '<img class="logo-icon" src="' . e(url(setting('logo_icon'))) . '" alt="">';
    } else {
        $icon = LOGO_DIAMOND_SVG;
    }
    $parts = array_filter([
        setting('logo_left') !== '' ? '<span>' . e(setting('logo_left')) . '</span>' : '',
        $icon,
        setting('logo_right') !== '' ? '<span>' . e(setting('logo_right')) . '</span>' : '',
    ]);
    return '<a class="logo" href="' . $home . '" aria-label="На главную">' . implode('', $parts) . '</a>';
}

/** <link rel="icon">: загруженный в настройках значок или стандартный ромб. */
function favicon_tag(): string
{
    $icon = setting('favicon');
    if ($icon === '') {
        return '<link rel="icon" href="' . e(asset('assets/favicon.svg')) . '" type="image/svg+xml">';
    }
    $types = ['svg' => 'image/svg+xml', 'ico' => 'image/x-icon'];
    $type = $types[pathinfo($icon, PATHINFO_EXTENSION)] ?? 'image/png';
    return '<link rel="icon" href="' . e(url($icon)) . '" type="' . $type . '">';
}

/** Разделы с опубликованными страницами — для шапки и подвала. */
function site_menu(): array
{
    static $menu = null;
    if ($menu !== null) {
        return $menu;
    }
    $sections = db_all('SELECT * FROM sections WHERE in_menu = 1 ORDER BY sort, id');
    $pages = db_all('SELECT id, section_id, slug, title FROM pages WHERE published = 1 ORDER BY sort, id');
    $bySection = [];
    foreach ($pages as $p) {
        $bySection[$p['section_id']][] = $p;
    }
    foreach ($sections as &$s) {
        $s['pages'] = $bySection[$s['id']] ?? [];
    }
    return $menu = $sections;
}

/** Цепочка категорий от корня до $id включительно. */
function category_trail(int $id): array
{
    $trail = [];
    for ($depth = 0; $id && $depth < 20; $depth++) {
        $c = db_one('SELECT * FROM categories WHERE id = ?', [$id]);
        if (!$c) {
            break;
        }
        array_unshift($trail, $c);
        $id = (int)$c['parent_id'];
    }
    return $trail;
}

/** Адрес страницы каталога: /раздел/категория/подкатегория[/инструкция]. */
function catalog_url(array $section, array $trail = [], $materialSlug = null): string
{
    $parts = [$section['slug']];
    foreach ($trail as $c) {
        $parts[] = $c['slug'];
    }
    if ($materialSlug !== null) {
        $parts[] = $materialSlug;
    }
    return url('/' . implode('/', $parts));
}

/** Короткая подпись ссылки: домен для внешней, путь для внутренней. */
function link_host(string $link): string
{
    $host = parse_url($link, PHP_URL_HOST);
    return $host ? preg_replace('~^www\.~i', '', $host) : $link;
}

function is_external_link(string $link): bool
{
    return (bool)preg_match('~^(https?:)?//~i', $link);
}

/** Куда ведёт вкладка меню, если это не выпадающий список. */
function section_href(array $s): string
{
    return $s['type'] === 'link' ? link_href($s['link']) : url('/' . $s['slug']);
}

// ---- сессия / CSRF / flash (используются только в админке) ----

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check()
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected = $_SESSION['csrf'] ?? '';
    if (!is_string($sent) || $expected === '' || !hash_equals($expected, $sent)) {
        http_response_code(403);
        exit('Сессия устарела. Вернитесь назад и обновите страницу.');
    }
}

function flash($msg = null, string $type = 'ok')
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    // Битые байты (не UTF-8) заменяем, чтобы в базу не попадал нечитаемый текст.
    return is_string($v) ? trim(mb_convert_encoding($v, 'UTF-8', 'UTF-8')) : $default;
}
