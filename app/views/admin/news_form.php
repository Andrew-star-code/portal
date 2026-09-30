<?php /** @var ?array $item @var array $v @var array $errors */
$v['published_at'] = date('Y-m-d\TH:i', strtotime($v['published_at'] ?? 'now') ?: time());
?>
<div class="head">
  <h1><?= $item ? 'Редактирование новости' : 'Новая новость' ?></h1>
  <?php if ($item): ?>
    <?php if ($item['is_published']): ?><a class="btn" href="<?= e(url('/news/' . $item['slug'])) ?>" target="_blank" rel="noopener">На сайте ↗</a><?php endif ?>
    <?= view('admin/_delete', ['action' => '/admin/news/' . $item['id'] . '/delete', 'what' => 'новость']) ?>
  <?php endif ?>
</div>

<form method="post" enctype="multipart/form-data" class="form" action="<?= e(url('/admin/news/' . ($item['id'] ?? 'new'))) ?>">
  <?= csrf_field() ?>
  <?= f_text('title', 'Заголовок', $v, $errors, ['required' => true, 'maxlength' => 300]) ?>
  <div class="row">
    <?= f_text('published_at', 'Дата публикации', $v, $errors, ['type' => 'datetime-local']) ?>
    <?= f_text('slug', 'Адрес (slug)', $v, $errors, ['placeholder' => 'сформируется из заголовка', 'hint' => 'Латиница, цифры и дефисы: /news/<b>адрес</b>']) ?>
  </div>
  <?= f_textarea('lead', 'Анонс (краткое описание для списка)', $v, $errors, 3) ?>
  <?= f_image('image', 'Обложка', $v, $errors) ?>
  <?= f_editor('body', 'Текст', $v) ?>
  <?= f_check('is_published', 'Опубликовано', $v) ?>
  <div class="actions"><button class="btn btn--primary">Сохранить</button><a class="btn" href="<?= e(url('/admin/news')) ?>">К списку</a></div>
</form>
