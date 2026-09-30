<?php /** @var array $sections @var array $bySection */ ?>
<div class="head"><h1>Страницы</h1><a class="btn btn--primary" href="<?= e(url('/admin/pages/new')) ?>">+ Добавить</a></div>
<p class="muted">Страницы сгруппированы по разделам меню. Порядок в меню задаётся полем «Сортировка» (меньше — выше).</p>

<?php foreach ($sections as $s): ?>
<h2><?= e($s['title']) ?> <small class="muted">/<?= e($s['slug']) ?><?= $s['in_menu'] ? '' : ' · скрыт из меню' ?></small></h2>
<table class="tbl">
  <thead><tr><th class="w-sort">Сорт.</th><th>Заголовок</th><th>Адрес</th><th>Статус</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($bySection[$s['id']] ?? [] as $p): ?>
    <tr>
      <td><?= (int)$p['sort'] ?></td>
      <td><a href="<?= e(url('/admin/pages/' . $p['id'])) ?>"><?= e($p['title']) ?></a></td>
      <td class="muted">/<?= e($s['slug'] . '/' . $p['slug']) ?></td>
      <td><?= $p['published'] ? '<span class="tag tag--ok">опубликована</span>' : '<span class="tag">скрыта</span>' ?></td>
      <td class="nowrap right"><?php if ($p['published']): ?><a href="<?= e(url('/' . $s['slug'] . '/' . $p['slug'])) ?>" target="_blank" rel="noopener">на сайте ↗</a><?php endif ?></td>
    </tr>
  <?php endforeach ?>
  <?php if (empty($bySection[$s['id']])): ?><tr><td colspan="5" class="muted">В разделе нет страниц — он не показывается в меню.</td></tr><?php endif ?>
  </tbody>
</table>
<?php endforeach ?>
