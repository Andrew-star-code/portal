<?php /** @var array $items */
$typeLabel = ['dropdown' => 'выпадающий список', 'catalog' => 'каталог', 'link' => 'ссылка'];
?>
<div class="head"><h1>Разделы меню</h1><a class="btn btn--primary" href="<?= e(url('/admin/sections/new')) ?>">+ Добавить вкладку</a></div>
<p class="muted">Разделы — вкладки верхнего меню и колонки подвала. Пункт «Новости» добавляется автоматически.</p>

<table class="tbl">
  <thead><tr><th class="w-sort">Сорт.</th><th>Название</th><th>Тип</th><th>Адрес</th><th>Содержимое</th><th>В меню</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($items as $s): ?>
    <tr>
      <td><?= (int)$s['sort'] ?></td>
      <td><a href="<?= e(url('/admin/sections/' . $s['id'])) ?>"><?= e($s['title']) ?></a></td>
      <td><span class="tag"><?= e($typeLabel[$s['type']] ?? $s['type']) ?></span></td>
      <td class="muted"><?= $s['type'] === 'link' ? '→ ' . e($s['link']) : '/' . e($s['slug']) ?></td>
      <td class="muted nowrap">
        <?php if ($s['type'] === 'dropdown'): ?><?= (int)$s['pages_count'] ?> <?= plural((int)$s['pages_count'], 'страница', 'страницы', 'страниц') ?>
        <?php elseif ($s['type'] === 'catalog'): ?><?= (int)$s['categories_count'] ?> кат. · <?= (int)$s['materials_count'] ?> инстр.
        <?php endif ?>
      </td>
      <td><?= $s['in_menu'] ? 'да' : '<span class="muted">нет</span>' ?></td>
      <td class="right nowrap">
        <?php if ($s['type'] === 'dropdown'): ?><a href="<?= e(url('/admin/pages/new') . '?section=' . $s['id']) ?>">+ страница</a>
        <?php elseif ($s['type'] === 'catalog'): ?><a href="<?= e(url('/admin/categories?section=' . $s['id'])) ?>">Управлять каталогом →</a>
        <?php endif ?>
      </td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
