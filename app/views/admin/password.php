<?php /** @var array $errors */ $v = []; ?>
<div class="head"><h1>Смена пароля</h1></div>

<form method="post" class="form form--narrow" action="<?= e(url('/admin/password')) ?>">
  <?= csrf_field() ?>
  <?= f_text('current', 'Текущий пароль', $v, $errors, ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
  <?= f_text('new', 'Новый пароль', $v, $errors, ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'hint' => 'Не короче 10 символов.']) ?>
  <?= f_text('confirm', 'Повторите новый пароль', $v, $errors, ['type' => 'password', 'required' => true, 'autocomplete' => 'new-password']) ?>
  <div class="actions"><button class="btn btn--primary">Изменить пароль</button></div>
</form>
