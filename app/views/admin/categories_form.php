<?php /** @var ?array $item @var array $v @var array $errors @var ?array $section @var array $parents */
if (!array_key_exists('parent_id', $v) && isset($_GET['parent'])) {
    $v['parent_id'] = (int)$_GET['parent'];
}
?>
<?php if (!$section): ?>
  <h1>Каталог не найден</h1>
  <p><a href="<?= e(url('/admin/categories')) ?>">К каталогам</a></p>
<?php else:
  $back = url('/admin/categories?section=' . $section['id']);
  $siteUrl = $item ? catalog_url($section, category_trail((int)$item['id'])) : null;
?>
<div class="head">
  <h1><?= $item ? 'Категория «' . e($item['title']) . '»' : 'Новая категория' ?></h1>
  <?php if ($item): ?>
    <a class="btn" href="<?= e(url('/admin/categories/new?section=' . $section['id'] . '&parent=' . $item['id'])) ?>">+ Подкатегория</a>
    <a class="btn" href="<?= e(url('/admin/materials/new?section=' . $section['id'] . '&category=' . $item['id'])) ?>">+ Инструкция</a>
    <?php if ($item['published']): ?><a class="btn" href="<?= e($siteUrl) ?>" target="_blank" rel="noopener">На сайте ↗</a><?php endif ?>
    <?= view('admin/_delete', ['action' => '/admin/categories/' . $item['id'] . '/delete', 'what' => 'категорию']) ?>
  <?php endif ?>
</div>

<form method="post" enctype="multipart/form-data" class="form form--narrow" action="<?= e(url('/admin/categories/' . ($item['id'] ?? 'new') . '?section=' . $section['id'])) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">
  <?= f_text('title', 'Название', $v, $errors, ['required' => true, 'maxlength' => 120]) ?>
  <?= f_select('parent_id', 'Где находится', ['' => '«' . $section['title'] . '» (верхний уровень)'] + $parents, $v, $errors) ?>
  <?= f_textarea('description', 'Описание (показывается под заголовком)', $v, $errors, 3) ?>
  <div class="row">
    <?= f_text('slug', 'Адрес (slug)', $v, $errors, ['placeholder' => 'сформируется из названия']) ?>
    <?= f_text('sort', 'Сортировка', $v + ['sort' => 100], $errors, ['type' => 'number']) ?>
  </div>
  <?= f_image('image', 'Обложка плитки (необязательно)', $v, $errors) ?>
  <?= f_check('published', 'Опубликована', $v) ?>
  <div class="actions"><button class="btn btn--primary">Сохранить</button><a class="btn" href="<?= e($back) ?>">К каталогу</a></div>
</form>
<?php endif ?>
