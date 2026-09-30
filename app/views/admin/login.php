<?php /** @var ?string $error @var string $login @var bool $noUsers */ ?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Вход — Админ-панель</title>
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
<?= favicon_tag() ?>
</head>
<body class="auth">
<form class="auth-box" method="post" action="<?= e(url('/admin/login')) ?>">
  <?php if (setting('logo_mode') === 'image' && setting('logo_image') !== ''): ?>
  <h1><img class="auth-logo" src="<?= e(url(setting('logo_image'))) ?>" alt="<?= e(setting('site_name')) ?>"></h1>
  <?php else: ?>
  <h1><?= e(setting('logo_left')) ?> <b><?= e(setting('logo_right')) ?></b></h1>
  <?php endif ?>
  <p class="muted">Вход в админ-панель</p>
  <?php if ($error): ?><div class="flash flash--error" role="alert"><?= e($error) ?></div><?php endif ?>
  <?php if ($noUsers): ?>
  <div class="flash flash--error">Администратор ещё не создан. Выполните на сервере: <code>php bin/create-admin.php admin</code></div>
  <?php endif ?>
  <?= csrf_field() ?>
  <label class="f"><span>Логин</span><input name="login" value="<?= e($login) ?>" required autofocus autocomplete="username"></label>
  <label class="f"><span>Пароль</span><input type="password" name="password" required autocomplete="current-password"></label>
  <button class="btn btn--primary btn--wide">Войти</button>
  <p><a href="<?= e(url('/')) ?>">← На сайт</a></p>
</form>
</body>
</html>
