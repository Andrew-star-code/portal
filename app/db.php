<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $path = config('db_path');
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException("Не удалось создать каталог $dir");
    }
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    db_migrate($pdo);
    return $pdo;
}

/**
 * Строка результата с числами в виде int/float, как в PHP 8.1+.
 * В PHP 7 драйвер SQLite отдаёт все значения строками, поэтому приводим их сами
 * по фактическому типу значения в базе.
 */
function db_fetch(PDOStatement $st)
{
    $row = $st->fetch();
    if ($row === false || PHP_VERSION_ID >= 80100) {
        return $row;
    }
    $i = 0;
    foreach ($row as $key => $value) {
        if ($value !== null) {
            $meta = $st->getColumnMeta($i);
            $type = $meta['native_type'] ?? '';
            if ($type === 'integer') {
                $row[$key] = (int)$value;
            } elseif ($type === 'double') {
                $row[$key] = (float)$value;
            }
        }
        $i++;
    }
    return $row;
}

function db_one(string $sql, array $params = [])
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $row = db_fetch($st);
    return $row === false ? null : $row;
}

function db_all(string $sql, array $params = []): array
{
    $st = db()->prepare($sql);
    $st->execute($params);
    $rows = [];
    while (($row = db_fetch($st)) !== false) {
        $rows[] = $row;
    }
    return $rows;
}

function db_exec(string $sql, array $params = []): int
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->rowCount();
}

/** Пошаговые миграции: каждая база доводится до последней версии без потери данных. */
function db_migrate(PDO $pdo)
{
    $steps = [1 => 'db_migrate_v1', 2 => 'db_migrate_v2', 3 => 'db_migrate_v3'];
    $version = (int)$pdo->query('PRAGMA user_version')->fetchColumn();
    foreach ($steps as $target => $fn) {
        if ($version >= $target) {
            continue;
        }
        $pdo->beginTransaction();
        $fn($pdo);
        $pdo->exec('PRAGMA user_version = ' . $target);
        $pdo->commit();
        $version = $target;
    }
}

function db_migrate_v1(PDO $pdo)
{
    foreach ([
        'CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            login TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL
        )',
        'CREATE TABLE sections (
            id INTEGER PRIMARY KEY,
            slug TEXT NOT NULL UNIQUE,
            title TEXT NOT NULL,
            sort INTEGER NOT NULL DEFAULT 0,
            in_menu INTEGER NOT NULL DEFAULT 1
        )',
        'CREATE TABLE pages (
            id INTEGER PRIMARY KEY,
            section_id INTEGER NOT NULL REFERENCES sections(id) ON DELETE RESTRICT,
            slug TEXT NOT NULL,
            title TEXT NOT NULL,
            body TEXT NOT NULL DEFAULT \'\',
            sort INTEGER NOT NULL DEFAULT 0,
            published INTEGER NOT NULL DEFAULT 1,
            updated_at TEXT NOT NULL,
            UNIQUE (section_id, slug)
        )',
        'CREATE TABLE news (
            id INTEGER PRIMARY KEY,
            slug TEXT NOT NULL UNIQUE,
            title TEXT NOT NULL,
            lead TEXT NOT NULL DEFAULT \'\',
            body TEXT NOT NULL DEFAULT \'\',
            image TEXT,
            published_at TEXT NOT NULL,
            is_published INTEGER NOT NULL DEFAULT 1,
            updated_at TEXT NOT NULL
        )',
        'CREATE INDEX news_feed ON news (is_published, published_at DESC)',
        'CREATE TABLE slides (
            id INTEGER PRIMARY KEY,
            title TEXT NOT NULL,
            motto TEXT NOT NULL DEFAULT \'\',
            text TEXT NOT NULL DEFAULT \'\',
            link TEXT NOT NULL DEFAULT \'\',
            theme INTEGER NOT NULL DEFAULT 1,
            image TEXT,
            sort INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1
        )',
        'CREATE TABLE settings (
            key TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )',
        'CREATE TABLE login_attempts (
            ip TEXT NOT NULL,
            at INTEGER NOT NULL
        )',
        'CREATE INDEX login_attempts_ip ON login_attempts (ip, at)',
    ] as $sql) {
        $pdo->exec($sql);
    }
    db_seed($pdo);
}

/** v2: типы вкладок, каталоги с деревом категорий и файлами-инструкциями. */
function db_migrate_v2(PDO $pdo)
{
    foreach ([
        "ALTER TABLE sections ADD COLUMN type TEXT NOT NULL DEFAULT 'dropdown'",
        "ALTER TABLE sections ADD COLUMN link TEXT NOT NULL DEFAULT ''",
        "ALTER TABLE sections ADD COLUMN body TEXT NOT NULL DEFAULT ''",
        'CREATE TABLE categories (
            id INTEGER PRIMARY KEY,
            section_id INTEGER NOT NULL REFERENCES sections(id) ON DELETE RESTRICT,
            parent_id INTEGER REFERENCES categories(id) ON DELETE RESTRICT,
            slug TEXT NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT \'\',
            image TEXT,
            sort INTEGER NOT NULL DEFAULT 0,
            published INTEGER NOT NULL DEFAULT 1
        )',
        'CREATE INDEX categories_tree ON categories (section_id, parent_id, sort)',
        'CREATE TABLE materials (
            id INTEGER PRIMARY KEY,
            section_id INTEGER NOT NULL REFERENCES sections(id) ON DELETE RESTRICT,
            category_id INTEGER REFERENCES categories(id) ON DELETE RESTRICT,
            slug TEXT NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT \'\',
            file_path TEXT NOT NULL,
            file_name TEXT NOT NULL,
            file_size INTEGER NOT NULL DEFAULT 0,
            kind TEXT NOT NULL,
            sort INTEGER NOT NULL DEFAULT 0,
            published INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )',
        'CREATE INDEX materials_cat ON materials (section_id, category_id, sort)',
    ] as $sql) {
        $pdo->exec($sql);
    }

    // Демо-каталог: вкладка «Системы проектирования» с тремя категориями.
    $exists = $pdo->query("SELECT 1 FROM sections WHERE slug = 'cad'")->fetchColumn();
    if (!$exists) {
        $sort = (int)$pdo->query('SELECT COALESCE(MAX(sort), 0) + 10 FROM sections')->fetchColumn();
        $pdo->prepare("INSERT INTO sections (slug, title, sort, type, body) VALUES ('cad', 'Системы проектирования', ?, 'catalog', ?)")
            ->execute([$sort, '<p>Инструкции по работе в системах автоматизированного проектирования.</p>']);
        $sid = (int)$pdo->lastInsertId();
        $ins = $pdo->prepare('INSERT INTO categories (section_id, slug, title, sort) VALUES (?, ?, ?, ?)');
        foreach ([['fusion', 'Fusion'], ['solidworks', 'SolidWorks'], ['kompas', 'Компас']] as $i => list($slug, $title)) {
            $ins->execute([$sid, $slug, $title, ($i + 1) * 10]);
        }
    }
}

/** Начальное наполнение — содержимое макета. */
/** Элементы каталога-ссылки: kind = 'link', адрес в url, файла нет. */
function db_migrate_v3(PDO $pdo)
{
    $pdo->exec("ALTER TABLE materials ADD COLUMN url TEXT NOT NULL DEFAULT ''");
}

function db_seed(PDO $pdo)
{
    $now = now();
    $stub = '<p>Раздел находится в наполнении. Текст страницы можно изменить в админ-панели.</p>';
    $motto = "Безопасность\nНадежность\nЭффективность";

    $settings = [
        'logo_left'  => 'ВЕКТА',
        'logo_right' => 'ПРИБОР',
        'site_name'  => 'Векта-Прибор — приборное бюро',
        'address'    => "000000, Россия,\nг. Пример, ул. Приборостроителей,\nд. 1",
        'phone'      => '',
        'email'      => '',
    ];
    $st = $pdo->prepare('INSERT INTO settings (key, value) VALUES (?, ?)');
    foreach ($settings as $k => $v) {
        $st->execute([$k, $v]);
    }

    $products = [
        ['avionics', 'Авионика', 'Бортовое оборудование и системы индикации для воздушных судов'],
        ['medical', 'Медицинское оборудование', 'Аппаратура для реанимации, анестезии и мониторинга пациентов'],
        ['asutp', 'АСУ ТП', 'Системы управления и контроля для энергетики'],
        ['auto', 'Автокомпоненты', 'Датчики и электронные блоки для автомобильной техники'],
        ['barometers', 'Барометры', 'Барометры-анероиды с точностью показаний ±0,8 гПа'],
    ];

    $sections = [
        ['buro', 'Бюро', [
            ['about', 'О предприятии', $stub],
            ['careers', 'Работа у нас', $stub],
            ['management', 'Руководство', $stub],
            ['contacts', 'Контакты', '<p>' . nl2br(e($settings['address']), false) . '</p>'],
            ['disclosure', 'Раскрытие информации', $stub],
        ]],
        ['products', 'Продукция', array_map(function ($p) { return [$p[0], $p[1], '<p>' . e($p[2]) . '.</p>']; }, $products)],
        ['competencies', 'Компетенции', [
            ['development', 'Разработка', $stub],
            ['production', 'Производство', $stub],
            ['testing', 'Испытания', $stub],
        ]],
        ['quality', 'Качество', [
            ['qms', 'Система менеджмента качества', $stub],
            ['certificates', 'Лицензии и сертификаты', $stub],
            ['service', 'Послепродажное обслуживание', $stub],
            ['repair', 'Текущий ремонт', $stub],
        ]],
    ];
    $insSection = $pdo->prepare('INSERT INTO sections (slug, title, sort) VALUES (?, ?, ?)');
    $insPage = $pdo->prepare('INSERT INTO pages (section_id, slug, title, body, sort, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($sections as $i => list($slug, $title, $pages)) {
        $insSection->execute([$slug, $title, ($i + 1) * 10]);
        $sid = (int)$pdo->lastInsertId();
        foreach ($pages as $j => list($pslug, $ptitle, $body)) {
            $insPage->execute([$sid, $pslug, $ptitle, $body, ($j + 1) * 10, $now]);
        }
    }

    $insSlide = $pdo->prepare('INSERT INTO slides (title, motto, text, link, theme, sort) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($products as $i => list($slug, $title, $text)) {
        $insSlide->execute([$title, $motto, $text, '/products/' . $slug, $i + 1, ($i + 1) * 10]);
    }

    $news = [
        ['2025-05-07', 'chtoby-pomnili-alleya', '«Чтобы помнили»: на предприятии высадили аллею в память о тех, кто ковал Победу в тылу'],
        ['2025-04-03', 'otkrytyy-urok-vektor-start', 'В школе радиоэлектроники «Вектор-Старт» прошёл открытый урок'],
        ['2025-03-12', 'ekspertnaya-otsenka', 'Потенциал бюро получил высокую экспертную оценку'],
        ['2025-01-24', 'sotrudnichestvo-s-universitetom', 'Бюро и технический университет расширяют сотрудничество'],
    ];
    $insNews = $pdo->prepare('INSERT INTO news (slug, title, lead, body, published_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($news as list($date, $slug, $title)) {
        $insNews->execute([$slug, $title, '', '<p>Текст новости можно изменить в админ-панели.</p>', $date . ' 09:00:00', $now]);
    }
}
