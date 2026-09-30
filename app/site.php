<?php
declare(strict_types=1);

function site_dispatch(array $seg): void
{
    $n = count($seg);
    if ($n === 0) {
        site_home();
    } elseif ($seg[0] === 'news' && $n === 1) {
        site_news_list();
    } elseif ($seg[0] === 'news' && $n === 2) {
        site_news_item($seg[1]);
    } elseif ($seg[0] === 'sitemap.xml' && $n === 1) {
        site_sitemap();
    } else {
        site_section($seg[0], array_slice($seg, 1));
    }
}

function render_site(string $view, array $vars, string $title = '', string $active = ''): void
{
    echo view('layout', [
        'title'   => $title,
        'active'  => $active,
        'bodyCls' => $vars['bodyCls'] ?? '',
        'content' => view($view, $vars),
    ]);
}

function site_home(): void
{
    $news = db_all('SELECT slug, title, published_at FROM news WHERE is_published = 1 ORDER BY published_at DESC, id DESC LIMIT 4');
    $slides = db_all('SELECT * FROM slides WHERE is_active = 1 ORDER BY sort, id');
    render_site('home', ['news' => $news, 'slides' => $slides, 'bodyCls' => 'is-home']);
}

function site_news_list(): void
{
    $per = max(1, (int)config('news_per_page'));
    $total = (int)db_one('SELECT COUNT(*) AS n FROM news WHERE is_published = 1')['n'];
    $pages = max(1, (int)ceil($total / $per));
    $page = max(1, (int)($_GET['page'] ?? 1));
    if ($page > $pages) {
        site_404();
        return;
    }
    $items = db_all(
        'SELECT slug, title, lead, image, published_at FROM news WHERE is_published = 1
         ORDER BY published_at DESC, id DESC LIMIT ? OFFSET ?',
        [$per, ($page - 1) * $per]
    );
    render_site('news_list', ['items' => $items, 'page' => $page, 'pages' => $pages], 'Новости', 'news');
}

function site_news_item(string $slug): void
{
    $item = db_one('SELECT * FROM news WHERE slug = ? AND is_published = 1', [$slug]);
    if (!$item) {
        site_404();
        return;
    }
    render_site('news_item', ['item' => $item], $item['title'], 'news');
}

function site_section(string $slug, array $rest): void
{
    $section = db_one('SELECT * FROM sections WHERE slug = ?', [$slug]);
    if (!$section) {
        site_404();
        return;
    }
    if ($section['type'] === 'catalog') {
        site_catalog($section, $rest);
        return;
    }
    if ($section['type'] === 'link') {
        if ($rest || $section['link'] === '') {
            site_404();
            return;
        }
        redirect(link_href($section['link']));
    }
    if (count($rest) > 1) {
        site_404();
        return;
    }
    if (!$rest) {
        $first = db_one('SELECT slug FROM pages WHERE section_id = ? AND published = 1 ORDER BY sort, id LIMIT 1', [$section['id']]);
        if (!$first) {
            site_404();
            return;
        }
        redirect('/' . $slug . '/' . $first['slug']);
    }
    site_page($section, $rest[0]);
}

/** Каталог: /раздел[/категория/.../подкатегория][/инструкция] */
function site_catalog(array $section, array $rest): void
{
    $trail = [];
    $parentId = null;
    foreach ($rest as $i => $slug) {
        $cat = db_one(
            'SELECT * FROM categories WHERE section_id = ? AND parent_id IS ? AND slug = ? AND published = 1',
            [$section['id'], $parentId, $slug]
        );
        if ($cat) {
            $trail[] = $cat;
            $parentId = (int)$cat['id'];
            continue;
        }
        // Не категория: допустимо только последним сегментом — это инструкция.
        $material = $i === count($rest) - 1 ? db_one(
            'SELECT * FROM materials WHERE section_id = ? AND category_id IS ? AND slug = ? AND published = 1',
            [$section['id'], $parentId, $slug]
        ) : null;
        if (!$material) {
            site_404();
            return;
        }
        $siblings = db_all(
            'SELECT slug, title, kind FROM materials WHERE section_id = ? AND category_id IS ? AND published = 1 AND id != ? ORDER BY sort, id',
            [$section['id'], $parentId, $material['id']]
        );
        render_site('material', ['section' => $section, 'trail' => $trail, 'm' => $material, 'siblings' => $siblings], $material['title'], $section['slug']);
        return;
    }

    $children = db_all(
        'SELECT c.*,
            (SELECT COUNT(*) FROM materials m WHERE m.category_id = c.id AND m.published = 1) AS materials_count,
            (SELECT COUNT(*) FROM categories s WHERE s.parent_id = c.id AND s.published = 1) AS children_count
         FROM categories c WHERE c.section_id = ? AND c.parent_id IS ? AND c.published = 1 ORDER BY c.sort, c.id',
        [$section['id'], $parentId]
    );
    $materials = db_all(
        'SELECT * FROM materials WHERE section_id = ? AND category_id IS ? AND published = 1 ORDER BY sort, id',
        [$section['id'], $parentId]
    );
    $current = $trail ? end($trail) : null;
    render_site(
        'catalog',
        ['section' => $section, 'trail' => $trail, 'current' => $current, 'children' => $children, 'materials' => $materials],
        $current['title'] ?? $section['title'],
        $section['slug']
    );
}

function site_page(array $section, string $pageSlug): void
{
    $page = db_one(
        'SELECT * FROM pages WHERE section_id = ? AND slug = ? AND published = 1',
        [$section['id'], $pageSlug]
    );
    if (!$page) {
        site_404();
        return;
    }
    $siblings = db_all('SELECT slug, title FROM pages WHERE section_id = ? AND published = 1 ORDER BY sort, id', [$section['id']]);
    render_site('page', ['section' => $section, 'page' => $page, 'siblings' => $siblings], $page['title'], $section['slug']);
}

function site_sitemap(): void
{
    $host = (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $urls = [url('/'), url('/news')];
    foreach (db_all('SELECT s.slug AS s, p.slug AS p FROM pages p JOIN sections s ON s.id = p.section_id WHERE p.published = 1 AND s.type = \'dropdown\'') as $r) {
        $urls[] = url("/{$r['s']}/{$r['p']}");
    }
    foreach (db_all('SELECT slug FROM news WHERE is_published = 1') as $r) {
        $urls[] = url('/news/' . $r['slug']);
    }
    foreach (db_all("SELECT * FROM sections WHERE type = 'catalog'") as $s) {
        $urls[] = catalog_url($s);
        foreach (db_all('SELECT id FROM categories WHERE section_id = ? AND published = 1', [$s['id']]) as $c) {
            $trail = category_trail((int)$c['id']);
            if (array_filter($trail, fn($t) => !$t['published'])) {
                continue;
            }
            $urls[] = catalog_url($s, $trail);
            foreach (db_all('SELECT slug FROM materials WHERE category_id = ? AND published = 1', [$c['id']]) as $m) {
                $urls[] = catalog_url($s, $trail, $m['slug']);
            }
        }
        foreach (db_all('SELECT slug FROM materials WHERE section_id = ? AND category_id IS NULL AND published = 1', [$s['id']]) as $m) {
            $urls[] = catalog_url($s, [], $m['slug']);
        }
    }
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>', "\n", '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $u) {
        echo '<url><loc>', e($host . $u), '</loc></url>';
    }
    echo '</urlset>';
}

function site_404(): void
{
    http_response_code(404);
    render_site('404', [], 'Страница не найдена');
}
