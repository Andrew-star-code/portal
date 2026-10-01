<?php /** @var string $title @var string $nav @var string $content @var ?array $flash */
$items = [
    'dashboard' => ['/admin', 'Обзор'],
    'news'      => ['/admin/news', 'Новости'],
    'pages'     => ['/admin/pages', 'Страницы'],
    'sections'  => ['/admin/sections', 'Разделы меню'],
    'categories' => ['/admin/categories', 'Каталоги'],
    'slides'    => ['/admin/slides', 'Слайды'],
    'settings'  => ['/admin/settings', 'Настройки'],
    'users'     => ['/admin/users', 'Пользователи'],
    'password'  => ['/admin/password', 'Смена пароля'],
];
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<meta name="upload-url" content="<?= e(url('/admin/upload')) ?>">
<title><?= e($title) ?> — Админ-панель</title>
<link rel="stylesheet" href="<?= e(asset('assets/fonts/roboto.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
<?= favicon_tag() ?>
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <a class="brand" href="<?= e(url('/admin')) ?>"><?php if (trim(setting('logo_left') . setting('logo_right')) !== ''): ?><?= e(setting('logo_left')) ?> <b><?= e(setting('logo_right')) ?></b><?php else: ?><?= e(setting('site_name')) ?><?php endif ?><small>админ-панель</small></a>
    <nav>
      <?php foreach ($items as $key => list($href, $label)): ?>
      <a href="<?= e(url($href)) ?>"<?= $nav === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach ?>
    </nav>
    <div class="sidebar-foot">
      <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Открыть сайт ↗</a>
      <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button class="linklike">Выйти (<?= e(current_user()['login'] ?? '') ?>)</button></form>
    </div>
  </aside>
  <main class="content">
    <?php if ($flash): ?><div class="flash flash--<?= e($flash['type']) ?>" role="status"><?= e($flash['msg']) ?></div><?php endif ?>
    <?= $content ?>
  </main>
</div>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
