<?php /** @var array $section @var array $trail @var array $m @var array $siblings */
$src = url($m['file_path']);
?>
<main class="main inner">
  <div class="wrap">
    <?= view('_catalog_crumbs', ['section' => $section, 'trail' => $trail, 'linkLast' => true]) ?>
    <h1 class="title"><?= e($m['title']) ?></h1>
    <?php if ($m['description'] !== ''): ?><p class="lead"><?= nl2br(e($m['description']), false) ?></p><?php endif ?>

    <div class="viewer">
      <?php if ($m['kind'] === 'video'): ?>
        <video controls preload="metadata" playsinline src="<?= e($src) ?>">
          Ваш браузер не поддерживает встроенное видео. <a href="<?= e($src) ?>" download="<?= e($m['file_name']) ?>">Скачайте файл</a>.
        </video>
      <?php else: ?>
        <iframe class="pdf-frame" src="<?= e($src) ?>#view=FitH" title="<?= e($m['title']) ?>"></iframe>
      <?php endif ?>
    </div>

    <div class="viewer-actions">
      <?php if ($m['kind'] === 'pdf'): ?><a class="btn-s" href="<?= e($src) ?>" target="_blank" rel="noopener">Открыть в новой вкладке</a><?php endif ?>
      <a class="btn-s btn-s--ghost" href="<?= e($src) ?>" download="<?= e($m['file_name']) ?>">Скачать (<?= e(human_size((int)$m['file_size'])) ?>)</a>
    </div>

    <?php if ($siblings): ?>
    <h2 class="subtitle">Другие материалы в разделе</h2>
    <ul class="sibling-list">
      <?php foreach ($siblings as $s): $isLink = $s['kind'] === 'link'; ?>
      <li><span class="kind kind--<?= e($s['kind']) ?> kind--sm" aria-hidden="true"><?= ['video' => '▶', 'link' => '↗'][$s['kind']] ?? 'PDF' ?></span><a href="<?= e($isLink ? link_href($s['url']) : catalog_url($section, $trail, $s['slug'])) ?>"<?= $isLink && is_external_link($s['url']) ? ' target="_blank" rel="noopener"' : '' ?>><?= e($s['title']) ?></a></li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>
  </div>
</main>
