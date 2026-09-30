<?php /** @var array $items @var int $page @var int $pages */ ?>
<main class="main inner">
  <div class="wrap">
    <nav class="crumbs" aria-label="Навигация"><a href="<?= e(url('/')) ?>">Главная</a> / <span>Новости</span></nav>
    <h1 class="title">Новости</h1>

    <?php if (!$items): ?><p class="muted">Новостей пока нет.</p><?php endif ?>

    <div class="news-list">
      <?php foreach ($items as $n): $href = url('/news/' . $n['slug']); ?>
      <article class="card<?= $n['image'] ? ' has-img' : '' ?>">
        <?php if ($n['image']): ?><a class="card-img" href="<?= e($href) ?>" tabindex="-1"><img src="<?= e(url($n['image'])) ?>" alt="" loading="lazy"></a><?php endif ?>
        <div>
          <span class="date"><?= e(fmt_date($n['published_at'])) ?></span>
          <h2><a href="<?= e($href) ?>"><?= e($n['title']) ?></a></h2>
          <?php if ($n['lead'] !== ''): ?><p><?= e($n['lead']) ?></p><?php endif ?>
        </div>
      </article>
      <?php endforeach ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Страницы">
      <?php for ($i = 1; $i <= $pages; $i++): ?>
        <?php if ($i === $page): ?><span aria-current="page"><?= $i ?></span>
        <?php else: ?><a href="<?= e(url('/news') . ($i > 1 ? '?page=' . $i : '')) ?>"><?= $i ?></a><?php endif ?>
      <?php endfor ?>
    </nav>
    <?php endif ?>
  </div>
</main>
