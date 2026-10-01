<?php
declare(strict_types=1);

const LOGO_MODES = ['text' => 'Конструктор: текст + значок + текст', 'image' => 'Картинка целиком'];
const LOGO_ICON_MODES = ['diamond' => 'Стандартный ромб', 'image' => 'Своя картинка', 'none' => 'Без значка'];
const SLIDE_THEMES =[1 => 'Синяя', 2 => 'Бирюзовая', 3 => 'Серо-голубая', 4 => 'Коричневая', 5 => 'Оливковая'];

function admin_dispatch(array $seg)
{
    admin_session_start();
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');

    $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    // Если тело запроса больше post_max_size, PHP молча выбрасывает все поля —
    // объясняем причину вместо «сессия устарела».
    if ($isPost && !$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        http_response_code(413);
        render_admin('message', ['text' => 'Файл больше лимита сервера (' . upload_limit_mb((int)config('file_max_mb')) . ' МБ). Изменения не сохранены.'], 'Слишком большой файл');
        return;
    }
    if ($isPost) {
        csrf_check();
    }

    $area = $seg[0] ?? '';
    $id = $seg[1] ?? null;
    $action = $seg[2] ?? null;

    if ($area === 'login') {
        admin_login($isPost);
        return;
    }
    if ($area === 'setup') {
        admin_setup($isPost);
        return;
    }

    require_login();

    switch ($area) {
        case '':
            admin_dashboard();
            return;
        case 'logout':
            if ($isPost) {
                logout();
            }
            redirect('/admin/login');
        case 'upload':
            if ($isPost) {
                admin_upload();
                return;
            }
            break;
        case 'settings':
            admin_settings($isPost);
            return;
        case 'password':
            admin_password($isPost);
            return;
        case 'news':
        case 'pages':
        case 'sections':
        case 'slides':
        case 'users':
        case 'categories':
        case 'materials':
            admin_crud($area, $id, $action, $isPost);
            return;
    }
    admin_404();
}

// ---------------------------------------------------------------- общее

function render_admin(string $view, array $vars = [], string $title = '', string $nav = '')
{
    echo view('admin/layout', [
        'title'   => $title,
        'nav'     => $nav,
        'flash'   => flash(),
        'content' => view('admin/' . $view, $vars),
    ]);
}

function admin_404()
{
    http_response_code(404);
    render_admin('message', ['text' => 'Страница не найдена.'], 'Не найдено');
}

function unique_slug(string $table, string $slug, $exceptId, string $scopeSql = '', array $scopeParams = []): string
{
    $candidate = $slug;
    for ($i = 2; ; $i++) {
        $params = array_merge([$candidate, $exceptId ?? 0], $scopeParams);
        $exists = db_one("SELECT 1 FROM $table WHERE slug = ? AND id != ? $scopeSql", $params);
        if (!$exists) {
            return $candidate;
        }
        $candidate = $slug . '-' . $i;
    }
}

/** Обрабатывает поле-картинку формы. Возвращает новое значение пути (или старое). */
function handle_image_field(string $field, $current, array &$errors, array $types = IMAGE_TYPES)
{
    $new = $current;
    if (!empty($_POST['remove_' . $field])) {
        $new = null;
    }
    $file = $_FILES[$field] ?? null;
    if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $new = save_uploaded_file($file, $types, (int)config('upload_max_mb'));
        } catch (RuntimeException $e) {
            $errors[$field] = $e->getMessage();
            return $current;
        }
    }
    return $new;
}

function to_db_datetime(string $s)
{
    $t = strtotime($s);
    return $t ? date('Y-m-d H:i:s', $t) : null;
}

// ---------------------------------------------------------------- вход

function admin_login(bool $isPost)
{
    if (current_user()) {
        redirect('/admin');
    }
    $error = null;
    $login = '';
    if ($isPost) {
        $login = post('login');
        if (login_blocked()) {
            $error = 'Слишком много неудачных попыток. Попробуйте через 15 минут.';
        } elseif (attempt_login($login, (string)($_POST['password'] ?? ''))) {
            redirect('/admin');
        } else {
            $error = 'Неверный логин или пароль.';
        }
    }
    $noUsers = !db_one('SELECT 1 FROM users LIMIT 1');
    echo view('admin/login', ['error' => $error, 'login' => $login, 'noUsers' => $noUsers]);
}

function admin_setup(bool $isPost)
{
    $key = (string)config('setup_key');
    if ($key === '' || db_one('SELECT 1 FROM users LIMIT 1')) {
        admin_404_bare();
        return;
    }
    $errors = [];
    $login = '';
    if ($isPost) {
        $login = post('login');
        $pass = (string)($_POST['password'] ?? '');
        if (!hash_equals($key, (string)($_POST['setup_key'] ?? ''))) {
            $errors[] = 'Неверный ключ установки.';
        }
        if (!preg_match('~^[a-zA-Z0-9._-]{3,40}$~', $login)) {
            $errors[] = 'Логин: 3–40 латинских букв, цифр, точек, дефисов.';
        }
        if ($p = password_problem($pass)) {
            $errors[] = $p;
        }
        if (!$errors) {
            db_exec('INSERT INTO users (login, password_hash, created_at) VALUES (?, ?, ?)', [$login, password_hash($pass, PASSWORD_DEFAULT), now()]);
            attempt_login($login, $pass);
            flash('Администратор создан. Уберите setup_key из config.php.');
            redirect('/admin');
        }
    }
    echo view('admin/setup', ['errors' => $errors, 'login' => $login]);
}

function admin_404_bare()
{
    http_response_code(404);
    echo 'Not found';
}

// ---------------------------------------------------------------- дашборд

function admin_dashboard()
{
    $counts = [
        'news'   => (int)db_one('SELECT COUNT(*) n FROM news')['n'],
        'pages'  => (int)db_one('SELECT COUNT(*) n FROM pages')['n'],
        'slides' => (int)db_one('SELECT COUNT(*) n FROM slides WHERE is_active = 1')['n'],
        'materials' => (int)db_one('SELECT COUNT(*) n FROM materials')['n'],
    ];
    $latest = db_all('SELECT id, title, published_at, is_published FROM news ORDER BY published_at DESC, id DESC LIMIT 5');
    render_admin('dashboard', ['counts' => $counts, 'latest' => $latest], 'Обзор', 'dashboard');
}

// ---------------------------------------------------------------- CRUD

function admin_crud(string $area, $id, $action, bool $isPost)
{
    $titles = [
        'news' => 'Новости', 'pages' => 'Страницы', 'sections' => 'Разделы', 'slides' => 'Слайды', 'users' => 'Пользователи',
        'categories' => 'Каталоги', 'materials' => 'Инструкции',
    ];
    $navKey = $area === 'materials' ? 'categories' : $area;

    if ($id === null) {
        $fn = 'admin_' . $area . '_index';
        render_admin($area . '_index', $fn(), $titles[$area], $navKey);
        return;
    }

    $item = null;
    if ($id !== 'new') {
        if (!ctype_digit($id) || !($item = db_one("SELECT * FROM $area WHERE id = ?", [(int)$id]))) {
            admin_404();
            return;
        }
    }

    if ($action === 'delete') {
        if (!$isPost || !$item) {
            admin_404();
            return;
        }
        $fn = 'admin_' . $area . '_delete';
        $err = $fn($item);
        $err ? flash($err, 'error') : flash('Удалено.');
        $back = 'admin_' . $area . '_back';
        if ($err) {
            redirect('/admin/' . $area . '/' . $item['id']);
        }
        redirect(function_exists($back) ? $back($item) : '/admin/' . $area);
    }
    if ($action !== null) {
        admin_404();
        return;
    }

    $errors = [];
    $values = $item ?? [];
    if ($isPost) {
        $fn = 'admin_' . $area . '_save';
        list($values, $errors, $newId) = $fn($item);
        if (!$errors) {
            flash('Сохранено.');
            redirect('/admin/' . $area . '/' . $newId);
        }
    }
    $fn = 'admin_' . $area . '_form_data';
    $extra = function_exists($fn) ? $fn($item, $values) : [];
    render_admin(
        $area . '_form',
        ['item' => $item, 'v' => $values, 'errors' => $errors] + $extra,
        ($item ? 'Редактирование' : 'Создание') . ' — ' . mb_strtolower($titles[$area]),
        $navKey
    );
}

// ---- новости

function admin_news_index(): array
{
    $per = 30;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $total = (int)db_one('SELECT COUNT(*) n FROM news')['n'];
    $items = db_all('SELECT id, slug, title, published_at, is_published FROM news ORDER BY published_at DESC, id DESC LIMIT ? OFFSET ?', [$per, ($page - 1) * $per]);
    return ['items' => $items, 'page' => $page, 'pages' => max(1, (int)ceil($total / $per))];
}

function admin_news_save($item): array
{
    $v = [
        'title'        => post('title'),
        'slug'         => post('slug'),
        'lead'         => post('lead'),
        'body'         => sanitize_html((string)($_POST['body'] ?? '')),
        'published_at' => to_db_datetime(post('published_at')) ?? now(),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'image'        => $item['image'] ?? null,
    ];
    $errors = [];
    if ($v['title'] === '') {
        $errors['title'] = 'Укажите заголовок.';
    }
    $v['slug'] = unique_slug('news', slugify($v['slug'] !== '' ? $v['slug'] : $v['title']), $item['id'] ?? null);
    $v['image'] = handle_image_field('image', $item['image'] ?? null, $errors);
    if ($errors) {
        if ($v['image'] !== ($item['image'] ?? null)) {
            delete_upload($v['image']);
            $v['image'] = $item['image'] ?? null;
        }
        return [$v, $errors, null];
    }

    $params = [$v['slug'], $v['title'], $v['lead'], $v['body'], $v['image'], $v['published_at'], $v['is_published'], now()];
    if ($item) {
        db_exec('UPDATE news SET slug=?, title=?, lead=?, body=?, image=?, published_at=?, is_published=?, updated_at=? WHERE id=?', array_merge($params, [$item['id']]));
        $id = (int)$item['id'];
        if ($item['image'] !== $v['image']) {
            delete_upload($item['image']);
        }
    } else {
        db_exec('INSERT INTO news (slug, title, lead, body, image, published_at, is_published, updated_at) VALUES (?,?,?,?,?,?,?,?)', $params);
        $id = (int)db()->lastInsertId();
    }
    return [$v, [], $id];
}

function admin_news_delete(array $item)
{
    db_exec('DELETE FROM news WHERE id = ?', [$item['id']]);
    delete_upload($item['image']);
    return null;
}

// ---- страницы

function admin_pages_index(): array
{
    $sections = db_all("SELECT * FROM sections s WHERE type = 'dropdown' OR EXISTS (SELECT 1 FROM pages p WHERE p.section_id = s.id) ORDER BY sort, id");
    $pages = db_all('SELECT id, section_id, slug, title, sort, published, updated_at FROM pages ORDER BY sort, id');
    $by = [];
    foreach ($pages as $p) {
        $by[$p['section_id']][] = $p;
    }
    return ['sections' => $sections, 'bySection' => $by];
}

function admin_pages_form_data($item = null): array
{
    return ['sections' => db_all("SELECT id, slug, title FROM sections WHERE type = 'dropdown' OR id = ? ORDER BY sort, id", [$item['section_id'] ?? 0])];
}

function admin_pages_save($item): array
{
    $v = [
        'section_id' => (int)post('section_id'),
        'title'      => post('title'),
        'slug'       => post('slug'),
        'body'       => sanitize_html((string)($_POST['body'] ?? '')),
        'sort'       => (int)post('sort', '0'),
        'published'  => isset($_POST['published']) ? 1 : 0,
    ];
    $errors = [];
    if ($v['title'] === '') {
        $errors['title'] = 'Укажите заголовок.';
    }
    if (!db_one('SELECT 1 FROM sections WHERE id = ?', [$v['section_id']])) {
        $errors['section_id'] = 'Выберите раздел.';
    }
    $v['slug'] = unique_slug('pages', slugify($v['slug'] !== '' ? $v['slug'] : $v['title']), $item['id'] ?? null, 'AND section_id = ?', [$v['section_id']]);
    if ($errors) {
        return [$v, $errors, null];
    }
    $params = [$v['section_id'], $v['slug'], $v['title'], $v['body'], $v['sort'], $v['published'], now()];
    if ($item) {
        db_exec('UPDATE pages SET section_id=?, slug=?, title=?, body=?, sort=?, published=?, updated_at=? WHERE id=?', array_merge($params, [$item['id']]));
        $id = (int)$item['id'];
    } else {
        db_exec('INSERT INTO pages (section_id, slug, title, body, sort, published, updated_at) VALUES (?,?,?,?,?,?,?)', $params);
        $id = (int)db()->lastInsertId();
    }
    return [$v, [], $id];
}

function admin_pages_delete(array $item)
{
    db_exec('DELETE FROM pages WHERE id = ?', [$item['id']]);
    return null;
}

// ---- разделы

function admin_sections_index(): array
{
    return ['items' => db_all(
        'SELECT s.*,
            (SELECT COUNT(*) FROM pages p WHERE p.section_id = s.id) AS pages_count,
            (SELECT COUNT(*) FROM categories c WHERE c.section_id = s.id) AS categories_count,
            (SELECT COUNT(*) FROM materials m WHERE m.section_id = s.id) AS materials_count
         FROM sections s ORDER BY sort, id'
    )];
}

const SECTION_TYPES = [
    'dropdown' => 'Выпадающий список страниц',
    'catalog'  => 'Своя страница (каталог с категориями и инструкциями)',
    'link'     => 'Ссылка на другую страницу или сайт',
];

function admin_sections_form_data($item): array
{
    return $item ? [
        'pagesCount'   => (int)db_one('SELECT COUNT(*) n FROM pages WHERE section_id = ?', [$item['id']])['n'],
        'catalogCount' => (int)db_one('SELECT (SELECT COUNT(*) FROM categories WHERE section_id = ?) + (SELECT COUNT(*) FROM materials WHERE section_id = ?) n', [$item['id'], $item['id']])['n'],
    ] : ['pagesCount' => 0, 'catalogCount' => 0];
}

function admin_sections_save($item): array
{
    $v = [
        'title'   => post('title'),
        'slug'    => post('slug'),
        'sort'    => (int)post('sort', '0'),
        'in_menu' => isset($_POST['in_menu']) ? 1 : 0,
        'type'    => post('type', 'dropdown'),
        'link'    => post('link'),
        'body'    => sanitize_html((string)($_POST['body'] ?? '')),
    ];
    $errors = [];
    if ($v['title'] === '') {
        $errors['title'] = 'Укажите название.';
    }
    if (!isset(SECTION_TYPES[$v['type']])) {
        $v['type'] = 'dropdown';
    }
    if ($v['type'] === 'link' && !preg_match('~^(/|https?://)~i', $v['link'])) {
        $errors['link'] = 'Ссылка должна начинаться с / (страница сайта) или с http(s)://';
    }
    $v['slug'] = slugify($v['slug'] !== '' ? $v['slug'] : $v['title']);
    if (in_array($v['slug'], ['admin', 'news', 'assets', 'uploads', 'sitemap-xml'], true)) {
        $errors['slug'] = 'Этот адрес зарезервирован.';
    }
    $v['slug'] = unique_slug('sections', $v['slug'], $item['id'] ?? null);
    if ($errors) {
        return [$v, $errors, null];
    }
    $params = [$v['slug'], $v['title'], $v['sort'], $v['in_menu'], $v['type'], $v['link'], $v['body']];
    if ($item) {
        db_exec('UPDATE sections SET slug=?, title=?, sort=?, in_menu=?, type=?, link=?, body=? WHERE id=?', array_merge($params, [$item['id']]));
        $id = (int)$item['id'];
    } else {
        db_exec('INSERT INTO sections (slug, title, sort, in_menu, type, link, body) VALUES (?,?,?,?,?,?,?)', $params);
        $id = (int)db()->lastInsertId();
    }
    return [$v, [], $id];
}

function admin_sections_delete(array $item)
{
    if (db_one('SELECT 1 FROM pages WHERE section_id = ?', [$item['id']])) {
        return 'В разделе есть страницы — сначала удалите или перенесите их.';
    }
    if (db_one('SELECT 1 FROM categories WHERE section_id = ? UNION SELECT 1 FROM materials WHERE section_id = ?', [$item['id'], $item['id']])) {
        return 'В каталоге есть категории или инструкции — сначала удалите их.';
    }
    db_exec('DELETE FROM sections WHERE id = ?', [$item['id']]);
    return null;
}

// ---- слайды

function admin_slides_index(): array
{
    return ['items' => db_all('SELECT * FROM slides ORDER BY sort, id')];
}

function admin_slides_save($item): array
{
    $v = [
        'title'     => post('title'),
        'motto'     => post('motto'),
        'text'      => post('text'),
        'link'      => post('link'),
        'theme'     => (int)post('theme', '1'),
        'sort'      => (int)post('sort', '0'),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'image'     => $item['image'] ?? null,
    ];
    $errors = [];
    if ($v['title'] === '') {
        $errors['title'] = 'Укажите заголовок.';
    }
    if (!isset(SLIDE_THEMES[$v['theme']])) {
        $v['theme'] = 1;
    }
    if ($v['link'] !== '' && !preg_match('~^(/|https?://)~i', $v['link'])) {
        $errors['link'] = 'Ссылка должна начинаться с / (страница сайта) или с http(s)://';
    }
    $v['image'] = handle_image_field('image', $item['image'] ?? null, $errors);
    if ($errors) {
        if ($v['image'] !== ($item['image'] ?? null)) {
            delete_upload($v['image']);
            $v['image'] = $item['image'] ?? null;
        }
        return [$v, $errors, null];
    }
    $params = [$v['title'], $v['motto'], $v['text'], $v['link'], $v['theme'], $v['image'], $v['sort'], $v['is_active']];
    if ($item) {
        db_exec('UPDATE slides SET title=?, motto=?, text=?, link=?, theme=?, image=?, sort=?, is_active=? WHERE id=?', array_merge($params, [$item['id']]));
        $id = (int)$item['id'];
        if ($item['image'] !== $v['image']) {
            delete_upload($item['image']);
        }
    } else {
        db_exec('INSERT INTO slides (title, motto, text, link, theme, image, sort, is_active) VALUES (?,?,?,?,?,?,?,?)', $params);
        $id = (int)db()->lastInsertId();
    }
    return [$v, [], $id];
}

function admin_slides_delete(array $item)
{
    db_exec('DELETE FROM slides WHERE id = ?', [$item['id']]);
    delete_upload($item['image']);
    return null;
}

// ---- каталоги: категории и инструкции

function catalog_sections(): array
{
    return db_all("SELECT id, slug, title FROM sections WHERE type = 'catalog' ORDER BY sort, id");
}

/** Раздел-каталог из ?section= или из сохраняемой записи; null — если не каталог. */
function catalog_section($item)
{
    $sid = $item['section_id'] ?? ($_GET['section'] ?? post('section_id'));
    return db_one("SELECT * FROM sections WHERE id = ? AND type = 'catalog'", [(int)$sid]);
}

/** Категории раздела в порядке дерева: [id => '— — Название'], без ветки $excludeId. */
function category_options(int $sectionId, $excludeId = null): array
{
    $byParent = [];
    foreach (db_all('SELECT id, parent_id, title FROM categories WHERE section_id = ? ORDER BY sort, id', [$sectionId]) as $c) {
        $byParent[(int)$c['parent_id']][] = $c;
    }
    $out = [];
    $walk = function (int $parent, int $depth) use (&$walk, &$out, $byParent, $excludeId) {
        foreach ($byParent[$parent] ?? [] as $c) {
            if ((int)$c['id'] === $excludeId) {
                continue;
            }
            $out[$c['id']] = str_repeat('— ', $depth) . $c['title'];
            if ($depth < 20) {
                $walk((int)$c['id'], $depth + 1);
            }
        }
    };
    $walk(0, 0);
    return $out;
}

/** Адрес уникален среди соседних категорий и инструкций (у них общее пространство URL). */
function unique_catalog_slug(string $slug, int $sectionId, $parentId, string $table, $exceptId): string
{
    $candidate = $slug;
    for ($i = 2; ; $i++) {
        $cat = db_one('SELECT 1 FROM categories WHERE section_id = ? AND parent_id IS ? AND slug = ? AND id != ?',
            [$sectionId, $parentId, $candidate, $table === 'categories' ? ($exceptId ?? 0) : 0]);
        $mat = db_one('SELECT 1 FROM materials WHERE section_id = ? AND category_id IS ? AND slug = ? AND id != ?',
            [$sectionId, $parentId, $candidate, $table === 'materials' ? ($exceptId ?? 0) : 0]);
        if (!$cat && !$mat) {
            return $candidate;
        }
        $candidate = $slug . '-' . $i;
    }
}

function admin_categories_index(): array
{
    $sections = catalog_sections();
    $current = null;
    foreach ($sections as $s) {
        if ((int)$s['id'] === (int)($_GET['section'] ?? 0)) {
            $current = $s;
        }
    }
    if ($current === null) {
        $current = $sections[0] ?? null;
    }
    if (!$current) {
        return ['sections' => [], 'current' => null];
    }
    $cats = [];
    foreach (db_all('SELECT * FROM categories WHERE section_id = ? ORDER BY sort, id', [$current['id']]) as $c) {
        $cats[(int)$c['parent_id']][] = $c;
    }
    $mats = [];
    foreach (db_all('SELECT id, category_id, slug, title, kind, file_size, published, sort FROM materials WHERE section_id = ? ORDER BY sort, id', [$current['id']]) as $m) {
        $mats[(int)$m['category_id']][] = $m;
    }
    return ['sections' => $sections, 'current' => $current, 'cats' => $cats, 'mats' => $mats];
}

function admin_categories_form_data($item, array $v): array
{
    $section = catalog_section($item);
    return [
        'section' => $section,
        'parents' => $section ? category_options((int)$section['id'], isset($item['id']) ? (int)$item['id'] : null) : [],
    ];
}

function admin_categories_save($item): array
{
    $section = catalog_section($item);
    $v = [
        'title'       => post('title'),
        'slug'        => post('slug'),
        'parent_id'   => post('parent_id') === '' ? null : (int)post('parent_id'),
        'description' => post('description'),
        'sort'        => (int)post('sort', '0'),
        'published'   => isset($_POST['published']) ? 1 : 0,
        'image'       => $item['image'] ?? null,
    ];
    $errors = [];
    if (!$section) {
        $errors['title'] = 'Раздел-каталог не найден.';
        return [$v, $errors, null];
    }
    $v['section_id'] = (int)$section['id'];
    if ($v['title'] === '') {
        $errors['title'] = 'Укажите название.';
    }
    if ($v['parent_id'] !== null) {
        $parent = db_one('SELECT id FROM categories WHERE id = ? AND section_id = ?', [$v['parent_id'], $v['section_id']]);
        $inOwnBranch = $item && in_array((int)$item['id'], array_map(function ($c) { return (int)$c['id']; }, category_trail($v['parent_id'])), true);
        if (!$parent || $inOwnBranch) {
            $errors['parent_id'] = 'Нельзя вложить категорию в саму себя или в свою подкатегорию.';
        }
    }
    $v['slug'] = unique_catalog_slug(slugify($v['slug'] !== '' ? $v['slug'] : $v['title']), $v['section_id'], $v['parent_id'], 'categories', $item['id'] ?? null);
    $v['image'] = handle_image_field('image', $item['image'] ?? null, $errors);
    if ($errors) {
        if ($v['image'] !== ($item['image'] ?? null)) {
            delete_upload($v['image']);
            $v['image'] = $item['image'] ?? null;
        }
        return [$v, $errors, null];
    }
    $params = [$v['parent_id'], $v['slug'], $v['title'], $v['description'], $v['image'], $v['sort'], $v['published']];
    if ($item) {
        db_exec('UPDATE categories SET parent_id=?, slug=?, title=?, description=?, image=?, sort=?, published=? WHERE id=?', array_merge($params, [$item['id']]));
        $id = (int)$item['id'];
        if ($item['image'] !== $v['image']) {
            delete_upload($item['image']);
        }
    } else {
        db_exec('INSERT INTO categories (parent_id, slug, title, description, image, sort, published, section_id) VALUES (?,?,?,?,?,?,?,?)', array_merge($params, [$v['section_id']]));
        $id = (int)db()->lastInsertId();
    }
    return [$v, [], $id];
}

function admin_categories_delete(array $item)
{
    if (db_one('SELECT 1 FROM categories WHERE parent_id = ? UNION SELECT 1 FROM materials WHERE category_id = ?', [$item['id'], $item['id']])) {
        return 'В категории есть подкатегории или инструкции — сначала удалите или перенесите их.';
    }
    db_exec('DELETE FROM categories WHERE id = ?', [$item['id']]);
    delete_upload($item['image']);
    return null;
}

function admin_categories_back(array $item): string
{
    return '/admin/categories?section=' . $item['section_id'];
}

function admin_materials_index(): array
{
    redirect('/admin/categories');
}

function admin_materials_form_data($item, array $v): array
{
    $section = catalog_section($item);
    return [
        'section'    => $section,
        'categories' => $section ? category_options((int)$section['id']) : [],
        'maxMb'      => upload_limit_mb((int)config('file_max_mb')),
    ];
}

function admin_materials_save($item): array
{
    $section = catalog_section($item);
    $v = [
        'title'       => post('title'),
        'slug'        => post('slug'),
        'category_id' => post('category_id') === '' ? null : (int)post('category_id'),
        'description' => post('description'),
        'sort'        => (int)post('sort', '0'),
        'published'   => isset($_POST['published']) ? 1 : 0,
        'file_path'   => $item['file_path'] ?? null,
        'file_name'   => $item['file_name'] ?? '',
        'file_size'   => $item['file_size'] ?? 0,
        'kind'        => $item['kind'] ?? '',
    ];
    $errors = [];
    if (!$section) {
        $errors['title'] = 'Раздел-каталог не найден.';
        return [$v, $errors, null];
    }
    $v['section_id'] = (int)$section['id'];
    if ($v['title'] === '') {
        $errors['title'] = 'Укажите название.';
    }
    if ($v['category_id'] !== null && !db_one('SELECT 1 FROM categories WHERE id = ? AND section_id = ?', [$v['category_id'], $v['section_id']])) {
        $errors['category_id'] = 'Выберите категорию.';
    }

    $newFile = null;
    $file = $_FILES['file'] ?? null;
    if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $newFile = save_uploaded_file($file, MATERIAL_TYPES, (int)config('file_max_mb'), 'files');
            $v['file_path'] = $newFile;
            $v['file_name'] = clean_file_name((string)$file['name'], $newFile);
            $v['file_size'] = (int)filesize(rtrim(config('uploads_dir'), '/\\') . '/' . substr($newFile, strlen('uploads/')));
            $v['kind'] = ends_with($newFile, '.pdf') ? 'pdf' : 'video';
        } catch (RuntimeException $e) {
            $errors['file'] = $e->getMessage();
        }
    } elseif (!$item) {
        $errors['file'] = 'Выберите файл PDF или видео.';
    }
    if ($v['title'] === '' && $newFile) {
        // Название по умолчанию — имя файла без расширения.
        $v['title'] = pathinfo($v['file_name'], PATHINFO_FILENAME);
        unset($errors['title']);
    }
    $v['slug'] = unique_catalog_slug(slugify($v['slug'] !== '' ? $v['slug'] : $v['title']), $v['section_id'], $v['category_id'], 'materials', $item['id'] ?? null);

    if ($errors) {
        if ($newFile) {
            delete_upload($newFile);
            foreach (['file_path', 'file_name', 'file_size', 'kind'] as $k) {
                $v[$k] = $item[$k] ?? null;
            }
        }
        return [$v, $errors, null];
    }

    $params = [$v['category_id'], $v['slug'], $v['title'], $v['description'], $v['file_path'], $v['file_name'], $v['file_size'], $v['kind'], $v['sort'], $v['published'], now()];
    if ($item) {
        db_exec('UPDATE materials SET category_id=?, slug=?, title=?, description=?, file_path=?, file_name=?, file_size=?, kind=?, sort=?, published=?, updated_at=? WHERE id=?', array_merge($params, [$item['id']]));
        $id = (int)$item['id'];
        if ($newFile && $item['file_path'] !== $newFile) {
            delete_upload($item['file_path']);
        }
    } else {
        db_exec('INSERT INTO materials (category_id, slug, title, description, file_path, file_name, file_size, kind, sort, published, updated_at, section_id, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', array_merge($params, [$v['section_id'], now()]));
        $id = (int)db()->lastInsertId();
    }
    return [$v, [], $id];
}

/** Имя для скачивания: исходное имя без путей и служебных символов, с настоящим расширением. */
function clean_file_name(string $original, string $savedPath): string
{
    $ext = pathinfo($savedPath, PATHINFO_EXTENSION);
    $base = pathinfo(basename(str_replace('\\', '/', $original)), PATHINFO_FILENAME);
    $base = trim((string)preg_replace('~[\x00-\x1f"<>:/\\\\|?*]+~u', ' ', $base));
    return mb_substr($base !== '' ? $base : 'file', 0, 120) . '.' . $ext;
}

function admin_materials_delete(array $item)
{
    db_exec('DELETE FROM materials WHERE id = ?', [$item['id']]);
    delete_upload($item['file_path']);
    return null;
}

function admin_materials_back(array $item): string
{
    return '/admin/categories?section=' . $item['section_id'];
}

// ---- пользователи

function admin_users_index(): array
{
    return ['items' => db_all('SELECT id, login, created_at FROM users ORDER BY login'), 'me' => current_user()];
}

function admin_users_save($item): array
{
    $v = ['login' => post('login')];
    $pass = (string)($_POST['password'] ?? '');
    $errors = [];
    if (!preg_match('~^[a-zA-Z0-9._-]{3,40}$~', $v['login'])) {
        $errors['login'] = 'Логин: 3–40 латинских букв, цифр, точек, дефисов.';
    } elseif (db_one('SELECT 1 FROM users WHERE login = ? AND id != ?', [$v['login'], $item['id'] ?? 0])) {
        $errors['login'] = 'Такой логин уже есть.';
    }
    if (!$item || $pass !== '') {
        if ($p = password_problem($pass)) {
            $errors['password'] = $p;
        }
    }
    if ($errors) {
        return [$v, $errors, null];
    }
    if ($item) {
        db_exec('UPDATE users SET login = ? WHERE id = ?', [$v['login'], $item['id']]);
        if ($pass !== '') {
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $item['id']]);
        }
        $id = (int)$item['id'];
    } else {
        db_exec('INSERT INTO users (login, password_hash, created_at) VALUES (?, ?, ?)', [$v['login'], password_hash($pass, PASSWORD_DEFAULT), now()]);
        $id = (int)db()->lastInsertId();
    }
    return [$v, [], $id];
}

function admin_users_delete(array $item)
{
    if ((int)$item['id'] === (int)current_user()['id']) {
        return 'Нельзя удалить самого себя.';
    }
    db_exec('DELETE FROM users WHERE id = ?', [$item['id']]);
    return null;
}

// ---------------------------------------------------------------- настройки, пароль, загрузка

function admin_settings(bool $isPost)
{
    $keys = ['site_name', 'logo_mode', 'logo_left', 'logo_icon_mode', 'logo_right', 'address', 'phone', 'email'];
    $images = ['logo_image' => LOGO_TYPES, 'logo_icon' => LOGO_TYPES, 'favicon' => FAVICON_TYPES]; // настройки-картинки и их форматы
    $errors = [];
    $current = [];
    foreach ($images as $k => $_) {
        $current[$k] = setting($k) ?: null;
    }
    if ($isPost) {
        $v = [];
        foreach ($keys as $k) {
            $v[$k] = post($k);
        }
        if ($v['site_name'] === '') {
            $errors['site_name'] = 'Укажите название сайта.';
        }
        if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Некорректный e-mail.';
        }
        if (!isset(LOGO_MODES[$v['logo_mode']])) {
            $v['logo_mode'] = 'text';
        }
        foreach ($images as $k => $types) {
            $v[$k] = handle_image_field($k, $current[$k], $errors, $types);
        }
        if (!isset(LOGO_ICON_MODES[$v['logo_icon_mode']])) {
            $v['logo_icon_mode'] = 'diamond';
        }
        if ($v['logo_mode'] === 'image' && !$v['logo_image'] && !isset($errors['logo_image'])) {
            $errors['logo_image'] = 'Загрузите картинку логотипа или выберите конструктор.';
        }
        if ($v['logo_mode'] === 'text') {
            if ($v['logo_icon_mode'] === 'image' && !$v['logo_icon'] && !isset($errors['logo_icon'])) {
                $errors['logo_icon'] = 'Загрузите картинку значка или выберите стандартный ромб.';
            }
            if ($v['logo_left'] === '' && $v['logo_right'] === '' && $v['logo_icon_mode'] === 'none') {
                $errors['logo_left'] = 'Логотип получится пустым: укажите текст или выберите значок.';
            }
        }
        if ($errors) {
            // только что загруженные файлы не сохраняем — пользователь выберет их заново
            foreach ($images as $k => $_) {
                if ($v[$k] !== $current[$k]) {
                    delete_upload($v[$k]);
                }
                $v[$k] = $current[$k];
            }
        } else {
            $st = db()->prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)');
            foreach ($v as $k => $val) {
                $st->execute([$k, (string)$val]);
            }
            foreach ($images as $k => $_) {
                if ($current[$k] !== $v[$k]) {
                    delete_upload($current[$k]);
                }
            }
            flash('Настройки сохранены.');
            redirect('/admin/settings');
        }
    } else {
        $v = $current;
        foreach ($keys as $k) {
            $v[$k] = setting($k);
        }
        $v['logo_mode'] = $v['logo_mode'] ?: 'text';
        $v['logo_icon_mode'] = $v['logo_icon_mode'] ?: 'diamond';
    }
    render_admin('settings', ['v' => $v, 'errors' => $errors], 'Настройки', 'settings');
}

function admin_password(bool $isPost)
{
    $errors = [];
    if ($isPost) {
        $u = db_one('SELECT * FROM users WHERE id = ?', [current_user()['id']]);
        $new = (string)($_POST['new'] ?? '');
        if (!password_verify((string)($_POST['current'] ?? ''), $u['password_hash'])) {
            $errors['current'] = 'Текущий пароль неверен.';
        }
        if ($p = password_problem($new)) {
            $errors['new'] = $p;
        } elseif ($new !== (string)($_POST['confirm'] ?? '')) {
            $errors['confirm'] = 'Пароли не совпадают.';
        }
        if (!$errors) {
            db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            session_regenerate_id(true);
            flash('Пароль изменён.');
            redirect('/admin/password');
        }
    }
    render_admin('password', ['errors' => $errors], 'Смена пароля', 'password');
}

function admin_upload()
{
    header('Content-Type: application/json; charset=utf-8');
    try {
        $path = save_uploaded_image($_FILES['file'] ?? []);
        echo json_encode(['url' => url($path)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } catch (RuntimeException $e) {
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
}

// ---------------------------------------------------------------- хелперы полей формы

function f_error(array $errors, string $name): string
{
    return isset($errors[$name]) ? '<div class="f-err">' . e($errors[$name]) . '</div>' : '';
}

function f_text(string $name, string $label, array $v, array $errors, array $opt = []): string
{
    $type = $opt['type'] ?? 'text';
    $attrs = ($opt['required'] ?? false) ? ' required' : '';
    $attrs .= isset($opt['placeholder']) ? ' placeholder="' . e($opt['placeholder']) . '"' : '';
    $attrs .= isset($opt['maxlength']) ? ' maxlength="' . (int)$opt['maxlength'] . '"' : '';
    $attrs .= isset($opt['autocomplete']) ? ' autocomplete="' . e($opt['autocomplete']) . '"' : '';
    $value = $type === 'password' ? '' : ($v[$name] ?? '');
    $hint = isset($opt['hint']) ? '<div class="f-hint">' . $opt['hint'] . '</div>' : '';
    return '<label class="f"><span>' . e($label) . '</span><input type="' . e($type) . '" name="' . e($name) . '" value="' . e($value) . '"' . $attrs . '>' . $hint . f_error($errors, $name) . '</label>';
}

function f_textarea(string $name, string $label, array $v, array $errors, int $rows = 3, string $hint = ''): string
{
    return '<label class="f"><span>' . e($label) . '</span><textarea name="' . e($name) . '" rows="' . $rows . '">' . e($v[$name] ?? '') . '</textarea>'
        . ($hint ? '<div class="f-hint">' . $hint . '</div>' : '') . f_error($errors, $name) . '</label>';
}

function f_check(string $name, string $label, array $v, bool $default = true): string
{
    $checked = array_key_exists($name, $v) ? (bool)$v[$name] : $default;
    return '<label class="f-check"><input type="checkbox" name="' . e($name) . '" value="1"' . ($checked ? ' checked' : '') . '> ' . e($label) . '</label>';
}

function f_select(string $name, string $label, array $options, array $v, array $errors): string
{
    $h = '<label class="f"><span>' . e($label) . '</span><select name="' . e($name) . '">';
    foreach ($options as $val => $text) {
        $h .= '<option value="' . e($val) . '"' . ((string)($v[$name] ?? '') === (string)$val ? ' selected' : '') . '>' . e($text) . '</option>';
    }
    return $h . '</select>' . f_error($errors, $name) . '</label>';
}

/** Поле-картинка. $formats — подписи форматов: JPG, PNG, WebP, GIF, SVG, ICO. */
function f_image(string $name, string $label, array $v, array $errors, array $formats = ['JPG', 'PNG', 'WebP', 'GIF']): string
{
    $accept = [
        'JPG' => 'image/jpeg', 'PNG' => 'image/png', 'WebP' => 'image/webp', 'GIF' => 'image/gif',
        'SVG' => 'image/svg+xml,.svg', 'ICO' => 'image/x-icon,.ico',
    ];
    $last = array_pop($formats);
    $list = $formats ? implode(', ', $formats) . ' или ' . $last : $last;
    $formats[] = $last;

    $h = '<div class="f"><span>' . e($label) . '</span>';
    if (!empty($v[$name])) {
        $h .= '<div class="f-img"><img src="' . e(url($v[$name])) . '" alt=""><label class="f-check"><input type="checkbox" name="remove_' . e($name) . '" value="1"> Удалить изображение</label></div>';
    }
    $h .= '<input type="file" name="' . e($name) . '" accept="' . e(implode(',', array_map(function ($f) use ($accept) { return $accept[$f]; }, $formats))) . '">';
    $h .= '<div class="f-hint">' . e($list) . ', до ' . (int)config('upload_max_mb') . ' МБ.</div>';
    return $h . f_error($errors, $name) . '</div>';
}

function f_file(string $name, string $label, array $errors, string $accept, string $hint): string
{
    return '<div class="f"><span>' . e($label) . '</span><input type="file" name="' . e($name) . '" accept="' . e($accept) . '">'
        . '<div class="f-hint">' . $hint . '</div>' . f_error($errors, $name) . '</div>';
}

function f_editor(string $name, string $label, array $v): string
{
    return '<div class="f"><span>' . e($label) . '</span><div class="editor" data-editor><textarea name="' . e($name) . '" rows="16">' . e($v[$name] ?? '') . '</textarea></div></div>';
}
