<?php /** @var array $items @var array $me */ ?>
<div class="head"><h1>Пользователи</h1><a class="btn btn--primary" href="<?= e(url('/admin/users/new')) ?>">+ Добавить</a></div>

<table class="tbl">
  <thead><tr><th>Логин</th><th>Создан</th></tr></thead>
  <tbody>
  <?php foreach ($items as $u): ?>
    <tr>
      <td><a href="<?= e(url('/admin/users/' . $u['id'])) ?>"><?= e($u['login']) ?></a><?= (int)$u['id'] === (int)$me['id'] ? ' <span class="tag">это вы</span>' : '' ?></td>
      <td class="muted"><?= e(fmt_date($u['created_at'])) ?></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
