<?php /** @var array $sections @var ?array $current @var array $cats @var array $mats */ ?>
<div class="head">
  <h1>Каталоги</h1>
  <?php if ($current): ?>
    <a class="btn" href="<?= e(url('/' . $current['slug'])) ?>" target="_blank" rel="noopener">На сайте ↗</a>
    <a class="btn" href="<?= e(url('/admin/materials/new?section=' . $current['id'])) ?>">+ Инструкция</a>
    <a class="btn btn--primary" href="<?= e(url('/admin/categories/new?section=' . $current['id'])) ?>">+ Категория</a>
  <?php endif ?>
</div>

<?php if (!$current): ?>
  <p>Каталогов пока нет. Каталог — это вкладка меню с типом «Своя страница».
     <a href="<?= e(url('/admin/sections/new')) ?>">Создайте вкладку</a> и выберите этот тип.</p>
<?php else: ?>

<?php if (count($sections) > 1): ?>
<nav class="tabs">
  <?php foreach ($sections as $s): ?>
  <a href="<?= e(url('/admin/categories?section=' . $s['id'])) ?>"<?= $s['id'] === $current['id'] ? ' aria-current="page"' : '' ?>><?= e($s['title']) ?></a>
  <?php endforeach ?>
</nav>
<?php endif ?>

<p class="muted">Вкладка «<?= e($current['title']) ?>» → категории → подкатегории → инструкции. Порядок задаётся полем «Сортировка».
  <a href="<?= e(url('/admin/sections/' . $current['id'])) ?>">Настройки вкладки</a></p>

<?php
$renderMaterials = function (array $list) use ($current): string {
    $h = '';
    foreach ($list as $m) {
        $h .= '<li class="tree-mat' . ($m['published'] ? '' : ' is-off') . '">'
            . '<span class="kind-badge kind-badge--' . e($m['kind']) . '">' . ($m['kind'] === 'video' ? 'видео' : 'pdf') . '</span>'
            . '<a href="' . e(url('/admin/materials/' . $m['id'])) . '">' . e($m['title']) . '</a>'
            . '<span class="muted">' . e(human_size((int)$m['file_size'])) . ($m['published'] ? '' : ' · скрыта') . '</span></li>';
    }
    return $h;
};
$render = function (int $parent) use (&$render, $cats, $mats, $current, $renderMaterials): string {
    $h = '';
    foreach ($cats[$parent] ?? [] as $c) {
        $id = (int)$c['id'];
        $h .= '<li class="tree-cat' . ($c['published'] ? '' : ' is-off') . '"><div class="tree-row">'
            . '<a class="tree-title" href="' . e(url('/admin/categories/' . $id)) . '">' . e($c['title']) . '</a>'
            . ($c['published'] ? '' : '<span class="tag">скрыта</span>')
            . '<span class="tree-actions">'
            . '<a href="' . e(url('/admin/categories/new?section=' . $current['id'] . '&parent=' . $id)) . '">+ подкатегория</a>'
            . '<a href="' . e(url('/admin/materials/new?section=' . $current['id'] . '&category=' . $id)) . '">+ инструкция</a>'
            . '</span></div>';
        $inner = $render($id) . $renderMaterials($mats[$id] ?? []);
        $h .= $inner !== '' ? '<ul>' . $inner . '</ul>' : '';
        $h .= '</li>';
    }
    return $h;
};
$tree = $render(0) . $renderMaterials($mats[0] ?? []);
?>
<?php if ($tree === ''): ?>
  <p class="muted">Каталог пуст. Добавьте первую категорию.</p>
<?php else: ?>
  <ul class="tree"><?= $tree ?></ul>
<?php endif ?>

<?php endif ?>
