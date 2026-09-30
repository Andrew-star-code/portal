<?php /** @var array $section @var array $trail @var ?array $current @var array $children @var array $materials */
$kindLabel = ['pdf' => 'PDF', 'video' => 'Видео'];
?>
<main class="main inner">
  <div class="wrap">
    <?= view('_catalog_crumbs', ['section' => $section, 'trail' => $trail, 'linkLast' => false]) ?>
    <h1 class="title"><?= e($current['title'] ?? $section['title']) ?></h1>

    <?php if ($current && $current['description'] !== ''): ?>
      <p class="lead"><?= nl2br(e($current['description']), false) ?></p>
    <?php elseif (!$current && $section['body'] !== ''): ?>
      <div class="prose catalog-intro"><?= $section['body'] /* очищено sanitize_html при сохранении */ ?></div>
    <?php endif ?>

    <?php if ($children): ?>
    <div class="tiles">
      <?php foreach ($children as $c): ?>
      <a class="tile<?= $c['image'] ? ' has-img' : '' ?>" href="<?= e(catalog_url($section, [...$trail, $c])) ?>">
        <?php if ($c['image']): ?><img src="<?= e(url($c['image'])) ?>" alt="" loading="lazy"><?php endif ?>
        <span class="tile-title"><?= e($c['title']) ?></span>
        <span class="tile-meta"><?php
          $parts = [];
          if ($c['children_count']) $parts[] = $c['children_count'] . ' ' . plural((int)$c['children_count'], 'раздел', 'раздела', 'разделов');
          if ($c['materials_count']) $parts[] = $c['materials_count'] . ' ' . plural((int)$c['materials_count'], 'инструкция', 'инструкции', 'инструкций');
          echo e($parts ? implode(' · ', $parts) : 'пока пусто');
        ?></span>
      </a>
      <?php endforeach ?>
    </div>
    <?php endif ?>

    <?php if ($materials): ?>
    <?php if ($children): ?><h2 class="subtitle">Инструкции</h2><?php endif ?>
    <ul class="materials">
      <?php foreach ($materials as $m): $href = catalog_url($section, $trail, $m['slug']); ?>
      <li class="material">
        <span class="kind kind--<?= e($m['kind']) ?>" aria-hidden="true"><?= $m['kind'] === 'video' ? '▶' : 'PDF' ?></span>
        <div class="material-body">
          <a class="material-title" href="<?= e($href) ?>"><?= e($m['title']) ?></a>
          <?php if ($m['description'] !== ''): ?><p><?= e($m['description']) ?></p><?php endif ?>
          <span class="material-meta"><?= e($kindLabel[$m['kind']] ?? '') ?> · <?= e(human_size((int)$m['file_size'])) ?></span>
        </div>
        <div class="material-actions">
          <a class="btn-s" href="<?= e($href) ?>">Открыть</a>
          <a class="btn-s btn-s--ghost" href="<?= e(url($m['file_path'])) ?>" download="<?= e($m['file_name']) ?>">Скачать</a>
        </div>
      </li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>

    <?php if (!$children && !$materials): ?>
      <p class="muted">В этом разделе пока нет материалов.</p>
    <?php endif ?>
  </div>
</main>
