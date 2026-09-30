<?php /** @var ?array $item @var array $v @var array $errors @var array $sections */
$opts = [];
$slugs = [];
foreach ($sections as $s) {
    $opts[$s['id']] = $s['title'];
    $slugs[$s['id']] = $s['slug'];
}
if (!isset($v['section_id']) && isset($_GET['section'])) {
    $v['section_id'] = (int)$_GET['section'];
}
?>
<div class="head">
  <h1><?= $item ? 'Редактирование страницы' : 'Новая страница' ?></h1>
  <?php if ($item): ?>
    <?php if ($item['published'] && isset($slugs[$item['section_id']])): ?><a class="btn" href="<?= e(url('/' . $slugs[$item['section_id']] . '/' . $item['slug'])) ?>" target="_blank" rel="noopener">На сайте ↗</a><?php endif ?>
    <?= view('admin/_delete', ['action' => '/admin/pages/' . $item['id'] . '/delete', 'what' => 'страницу']) ?>
  <?php endif ?>
</div>

<?php if (!$sections): ?>
<p>Сначала <a href="<?= e(url('/admin/sections/new')) ?>">создайте раздел</a>.</p>
<?php else: ?>
<form method="post" class="form" action="<?= e(url('/admin/pages/' . ($item['id'] ?? 'new'))) ?>">
  <?= csrf_field() ?>
  <?= f_text('title', 'Заголовок', $v, $errors, ['required' => true, 'maxlength' => 200]) ?>
  <div class="row">
    <?= f_select('section_id', 'Раздел', $opts, $v, $errors) ?>
    <?= f_text('slug', 'Адрес (slug)', $v, $errors, ['placeholder' => 'сформируется из заголовка']) ?>
    <?= f_text('sort', 'Сортировка', $v + ['sort' => 100], $errors, ['type' => 'number']) ?>
  </div>
  <?= f_editor('body', 'Содержимое', $v) ?>
  <?= f_check('published', 'Опубликована (видна на сайте и в меню)', $v) ?>
  <div class="actions"><button class="btn btn--primary">Сохранить</button><a class="btn" href="<?= e(url('/admin/pages')) ?>">К списку</a></div>
</form>
<?php endif ?>
