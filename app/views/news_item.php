<?php /** @var array $item */ ?>
<main class="main inner">
  <div class="wrap wrap--narrow">
    <nav class="crumbs" aria-label="Навигация"><a href="<?= e(url('/')) ?>">Главная</a> / <a href="<?= e(url('/news')) ?>">Новости</a></nav>
    <article>
      <span class="date"><?= e(fmt_date($item['published_at'])) ?></span>
      <h1 class="title"><?= e($item['title']) ?></h1>
      <?php if ($item['lead'] !== ''): ?><p class="lead"><?= e($item['lead']) ?></p><?php endif ?>
      <?php if ($item['image']): ?><img class="cover" src="<?= e(url($item['image'])) ?>" alt=""><?php endif ?>
      <div class="prose"><?= $item['body'] /* очищено sanitize_html при сохранении */ ?></div>
    </article>
    <p><a class="back" href="<?= e(url('/news')) ?>">← Все новости</a></p>
  </div>
</main>
