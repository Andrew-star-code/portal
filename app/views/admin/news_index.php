<?php /** @var array $items @var int $page @var int $pages */ ?>
<div class="head"><h1>Новости</h1><a class="btn btn--primary" href="<?= e(url('/admin/news/new')) ?>">+ Добавить</a></div>

<table class="tbl">
  <thead><tr><th>Дата</th><th>Заголовок</th><th>Статус</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $n): ?>
    <tr>
      <td class="nowrap"><?= e(fmt_date($n['published_at'])) ?></td>
      <td><a href="<?= e(url('/admin/news/' . $n['id'])) ?>"><?= e($n['title']) ?></a></td>
      <td><?= $n['is_published'] ? '<span class="tag tag--ok">опубликована</span>' : '<span class="tag">черновик</span>' ?></td>
      <td class="nowrap right"><?php if ($n['is_published']): ?><a href="<?= e(url('/news/' . $n['slug'])) ?>" target="_blank" rel="noopener">на сайте ↗</a><?php endif ?></td>
    </tr>
  <?php endforeach ?>
  <?php if (!$items): ?><tr><td colspan="4" class="muted">Новостей нет.</td></tr><?php endif ?>
  </tbody>
</table>

<?php if ($pages > 1): ?>
<nav class="pager">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <?php if ($i === $page): ?><span><?= $i ?></span><?php else: ?><a href="<?= e(url('/admin/news') . '?page=' . $i) ?>"><?= $i ?></a><?php endif ?>
  <?php endfor ?>
</nav>
<?php endif ?>
