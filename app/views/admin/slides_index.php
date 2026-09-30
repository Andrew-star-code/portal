<?php /** @var array $items */ ?>
<div class="head"><h1>Слайды главной</h1><a class="btn btn--primary" href="<?= e(url('/admin/slides/new')) ?>">+ Добавить</a></div>

<div class="slides-grid">
  <?php foreach ($items as $s): ?>
  <a class="slide-card t<?= (int)$s['theme'] ?><?= $s['is_active'] ? '' : ' is-off' ?>" href="<?= e(url('/admin/slides/' . $s['id'])) ?>"
     <?= $s['image'] ? 'style="background-image:linear-gradient(rgba(10,14,20,.55),rgba(10,14,20,.8)),url(\'' . e(url($s['image'])) . '\')"' : '' ?>>
    <small>#<?= (int)$s['sort'] ?><?= $s['is_active'] ? '' : ' · выключен' ?></small>
    <b><?= e($s['title']) ?></b>
    <span><?= e($s['text']) ?></span>
  </a>
  <?php endforeach ?>
</div>
<?php if (!$items): ?><p class="muted">Слайдов нет — правая половина главной будет пустой.</p><?php endif ?>
