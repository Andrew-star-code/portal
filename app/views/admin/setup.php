<?php /** @var array $errors @var string $login */ ?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Установка — Админ-панель</title>
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="auth">
<form class="auth-box" method="post" action="<?= e(url('/admin/setup')) ?>">
  <h1>Первый администратор</h1>
  <?php foreach ($errors as $err): ?><div class="flash flash--error"><?= e($err) ?></div><?php endforeach ?>
  <?= csrf_field() ?>
  <label class="f"><span>Ключ установки (setup_key из config.php)</span><input type="password" name="setup_key" required></label>
  <label class="f"><span>Логин</span><input name="login" value="<?= e($login) ?>" required autocomplete="username"></label>
  <label class="f"><span>Пароль (не короче 10 символов)</span><input type="password" name="password" required minlength="10" autocomplete="new-password"></label>
  <button class="btn btn--primary btn--wide">Создать</button>
</form>
</body>
</html>
