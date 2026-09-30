<?php /** @var array $news @var array $slides */ ?>
<main class="main home" id="top">
  <section class="news" id="news" aria-label="Новости">
    <?php foreach ($news as $n): ?>
    <article class="news-item"><span class="date"><?= e(fmt_date($n['published_at'])) ?></span><a href="<?= e(url('/news/' . $n['slug'])) ?>"><?= e($n['title']) ?></a></article>
    <?php endforeach ?>
    <?php if (!$news): ?>
    <article class="news-item"><p class="muted">Новостей пока нет.</p></article>
    <?php endif ?>
    <a class="news-all" href="<?= e(url('/news')) ?>">Все новости →</a>
  </section>

  <section class="slider" aria-roledescription="карусель" aria-label="Продукция">
    <?php foreach ($slides as $i => $s):
      $style = $s['image'] ? ' style="--img:url(\'' . e(url($s['image'])) . '\')"' : '';
    ?>
    <div class="slide s<?= (int)$s['theme'] ?><?= $s['image'] ? ' has-img' : '' ?><?= $i === 0 ? ' active' : '' ?>"<?= $style ?>>
      <?php if ($s['motto'] !== ''): ?><div class="motto"><?= nl2br(e($s['motto']), false) ?></div><?php endif ?>
      <h2><?php if ($s['link'] !== ''): ?><a href="<?= e(link_href($s['link'])) ?>"><?= e($s['title']) ?></a><?php else: ?><?= e($s['title']) ?><?php endif ?></h2>
      <p><?= e($s['text']) ?></p>
      <?php if ($s['link'] !== ''): ?><a class="slide-more" href="<?= e(link_href($s['link'])) ?>" tabindex="-1">Подробнее →</a><?php endif ?>
    </div>
    <?php endforeach ?>
    <div class="dots" role="tablist"></div>
  </section>
</main>
