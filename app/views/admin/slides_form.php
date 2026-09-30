<?php /** @var ?array $item @var array $v @var array $errors */ ?>
<div class="head">
  <h1><?= $item ? 'Редактирование слайда' : 'Новый слайд' ?></h1>
  <?php if ($item): ?><?= view('admin/_delete', ['action' => '/admin/slides/' . $item['id'] . '/delete', 'what' => 'слайд']) ?><?php endif ?>
</div>

<form method="post" enctype="multipart/form-data" class="form form--narrow" action="<?= e(url('/admin/slides/' . ($item['id'] ?? 'new'))) ?>">
  <?= csrf_field() ?>
  <?= f_text('title', 'Заголовок', $v, $errors, ['required' => true, 'maxlength' => 80]) ?>
  <?= f_textarea('motto', 'Девиз (каждая строка — с новой строки)', $v + ['motto' => "Безопасность\nНадежность\nЭффективность"], $errors, 3) ?>
  <?= f_textarea('text', 'Описание', $v, $errors, 2) ?>
  <?= f_text('link', 'Ссылка', $v, $errors, ['placeholder' => '/products/avionics', 'hint' => 'Страница сайта (/раздел/страница) или внешний адрес https://…']) ?>
  <div class="row">
    <?= f_select('theme', 'Цвет фона', SLIDE_THEMES, $v, $errors) ?>
    <?= f_text('sort', 'Сортировка', $v + ['sort' => 100], $errors, ['type' => 'number']) ?>
  </div>
  <?= f_image('image', 'Фоновое изображение (необязательно, заменяет цветной фон)', $v, $errors) ?>
  <?= f_check('is_active', 'Показывать на главной', $v) ?>
  <div class="actions"><button class="btn btn--primary">Сохранить</button><a class="btn" href="<?= e(url('/admin/slides')) ?>">К списку</a></div>
</form>
