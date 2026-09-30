<?php /** @var ?array $item @var array $v @var array $errors @var int $pagesCount @var int $catalogCount */
$v += ['type' => 'dropdown'];
?>
<div class="head">
  <h1><?= $item ? 'Редактирование раздела' : 'Новый раздел' ?></h1>
  <?php if ($item && $item['type'] === 'catalog'): ?><a class="btn" href="<?= e(url('/admin/categories?section=' . $item['id'])) ?>">Управлять каталогом →</a><?php endif ?>
  <?php if ($item && $item['type'] !== 'dropdown'): ?><a class="btn" href="<?= e(url('/' . $item['slug'])) ?>" target="_blank" rel="noopener">На сайте ↗</a><?php endif ?>
  <?php if ($item): ?><?= view('admin/_delete', ['action' => '/admin/sections/' . $item['id'] . '/delete', 'what' => 'раздел']) ?><?php endif ?>
</div>

<form method="post" class="form" action="<?= e(url('/admin/sections/' . ($item['id'] ?? 'new'))) ?>">
  <?= csrf_field() ?>
  <?= f_text('title', 'Название вкладки', $v, $errors, ['required' => true, 'maxlength' => 60]) ?>
  <?= f_select('type', 'Что происходит при нажатии на вкладку', SECTION_TYPES, $v, $errors) ?>

  <?php if ($pagesCount): ?>
  <p class="f-hint" data-show-if="type:catalog link">⚠ В разделе <?= $pagesCount ?> <?= plural($pagesCount, 'страница', 'страницы', 'страниц') ?>. При этом типе они сохранятся, но не будут видны в меню.</p>
  <?php endif ?>
  <?php if ($catalogCount): ?>
  <p class="f-hint" data-show-if="type:dropdown link">⚠ В каталоге есть категории и инструкции. При этом типе они сохранятся, но страница каталога будет недоступна.</p>
  <?php endif ?>

  <div data-show-if="type:link">
    <?= f_text('link', 'Адрес ссылки', $v, $errors, ['placeholder' => '/buro/contacts или https://…', 'hint' => 'Страница этого сайта (начинается с /) или внешний сайт (откроется в новой вкладке).']) ?>
  </div>
  <div data-show-if="type:catalog">
    <?= f_editor('body', 'Вводный текст на странице каталога (необязательно)', $v) ?>
  </div>

  <div class="row">
    <?= f_text('slug', 'Адрес раздела (slug)', $v, $errors, ['placeholder' => 'сформируется из названия', 'hint' => 'Смена адреса сломает старые ссылки на страницы раздела.']) ?>
    <?= f_text('sort', 'Сортировка', $v + ['sort' => 100], $errors, ['type' => 'number']) ?>
  </div>
  <?= f_check('in_menu', 'Показывать в меню и подвале', $v) ?>
  <div class="actions"><button class="btn btn--primary">Сохранить</button><a class="btn" href="<?= e(url('/admin/sections')) ?>">К списку</a></div>
</form>
