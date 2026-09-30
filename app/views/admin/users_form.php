<?php /** @var ?array $item @var array $v @var array $errors */ ?>
<div class="head">
  <h1><?= $item ? 'Пользователь ' . e($item['login']) : 'Новый пользователь' ?></h1>
  <?php if ($item && (int)$item['id'] !== (int)current_user()['id']): ?><?= view('admin/_delete', ['action' => '/admin/users/' . $item['id'] . '/delete', 'what' => 'пользователя']) ?><?php endif ?>
</div>

<form method="post" class="form form--narrow" action="<?= e(url('/admin/users/' . ($item['id'] ?? 'new'))) ?>" autocomplete="off">
  <?= csrf_field() ?>
  <?= f_text('login', 'Логин', $v, $errors, ['required' => true, 'maxlength' => 40, 'autocomplete' => 'off']) ?>
  <?= f_text('password', $item ? 'Новый пароль (оставьте пустым, чтобы не менять)' : 'Пароль', $v, $errors, ['type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'Не короче 10 символов.']) ?>
  <div class="actions"><button class="btn btn--primary">Сохранить</button><a class="btn" href="<?= e(url('/admin/users')) ?>">К списку</a></div>
</form>
