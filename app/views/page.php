<?php /** @var array $section @var array $page @var array $siblings */ ?>
<main class="main inner">
  <div class="wrap">
    <nav class="crumbs" aria-label="Навигация"><a href="<?= e(url('/')) ?>">Главная</a> / <span><?= e($section['title']) ?></span></nav>
    <div class="page-grid">
      <aside class="side">
        <h2><?= e($section['title']) ?></h2>
        <ul>
          <?php foreach ($siblings as $s): ?>
          <li><a href="<?= e(url("/{$section['slug']}/{$s['slug']}")) ?>"<?= $s['slug'] === $page['slug'] ? ' aria-current="page"' : '' ?>><?= e($s['title']) ?></a></li>
          <?php endforeach ?>
        </ul>
      </aside>
      <article>
        <h1 class="title"><?= e($page['title']) ?></h1>
        <div class="prose"><?= $page['body'] /* очищено sanitize_html при сохранении */ ?></div>
      </article>
    </div>
  </div>
</main>
