<?php /** @var array $counts @var array $latest */ ?>
<div class="head"><h1>Обзор</h1><a class="btn btn--primary" href="<?= e(url('/admin/news/new')) ?>">+ Новость</a></div>

<div class="stats">
  <a class="stat" href="<?= e(url('/admin/news')) ?>"><b><?= $counts['news'] ?></b><span>новостей</span></a>
  <a class="stat" href="<?= e(url('/admin/pages')) ?>"><b><?= $counts['pages'] ?></b><span>страниц</span></a>
  <a class="stat" href="<?= e(url('/admin/slides')) ?>"><b><?= $counts['slides'] ?></b><span>активных слайдов</span></a>
  <a class="stat" href="<?= e(url('/admin/categories')) ?>"><b><?= $counts['materials'] ?></b><span>инструкций</span></a>
</div>

<h2>Последние новости</h2>
<table class="tbl">
  <thead><tr><th>Дата</th><th>Заголовок</th><th>Статус</th></tr></thead>
  <tbody>
  <?php foreach ($latest as $n): ?>
    <tr>
      <td class="nowrap"><?= e(fmt_date($n['published_at'])) ?></td>
      <td><a href="<?= e(url('/admin/news/' . $n['id'])) ?>"><?= e($n['title']) ?></a></td>
      <td><?= $n['is_published'] ? '<span class="tag tag--ok">опубликована</span>' : '<span class="tag">черновик</span>' ?></td>
    </tr>
  <?php endforeach ?>
  </tbody>
</table>
