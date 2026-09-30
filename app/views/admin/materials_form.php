<?php /** @var ?array $item @var array $v @var array $errors @var ?array $section @var array $categories @var int $maxMb */
if (!array_key_exists('category_id', $v) && isset($_GET['category'])) {
    $v['category_id'] = (int)$_GET['category'];
}
?>
<?php if (!$section): ?>
  <h1>Каталог не найден</h1>
  <p><a href="<?= e(url('/admin/categories')) ?>">К каталогам</a></p>
<?php else:
  $back = url('/admin/categories?section=' . $section['id']);
  $siteUrl = $item ? catalog_url($section, $item['category_id'] ? category_trail((int)$item['category_id']) : [], $item['slug']) : null;
?>
<div class="head">
  <h1><?= $item ? 'Инструкция «' . e($item['title']) . '»' : 'Новая инструкция' ?></h1>
  <?php if ($item): ?>
    <?php if ($item['published']): ?><a class="btn" href="<?= e($siteUrl) ?>" target="_blank" rel="noopener">На сайте ↗</a><?php endif ?>
    <?= view('admin/_delete', ['action' => '/admin/materials/' . $item['id'] . '/delete', 'what' => 'инструкцию вместе с файлом']) ?>
  <?php endif ?>
</div>

<form method="post" enctype="multipart/form-data" class="form form--narrow" data-upload-progress action="<?= e(url('/admin/materials/' . ($item['id'] ?? 'new') . '?section=' . $section['id'])) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="section_id" value="<?= (int)$section['id'] ?>">

  <?php if (!empty($v['file_path'])): ?>
  <div class="f"><span>Текущий файл</span>
    <div class="file-current">
      <span class="kind-badge kind-badge--<?= e($v['kind']) ?>"><?= $v['kind'] === 'video' ? 'видео' : 'pdf' ?></span>
      <a href="<?= e(url($v['file_path'])) ?>" target="_blank" rel="noopener"><?= e($v['file_name']) ?></a>
      <span class="muted"><?= e(human_size((int)$v['file_size'])) ?></span>
    </div>
  </div>
  <?php endif ?>
  <?= f_file('file', $item ? 'Заменить файл' : 'Файл', $errors, 'application/pdf,video/mp4,video/webm,.pdf,.mp4,.webm',
      'PDF, MP4 или WebM, до ' . $maxMb . ' МБ. Видео лучше сохранять в MP4 (H.264) — оно играет во всех браузерах.') ?>

  <?= f_text('title', 'Название', $v, $errors, ['maxlength' => 200, 'placeholder' => $item ? '' : 'если пусто — возьмётся имя файла']) ?>
  <?= f_select('category_id', 'Категория', ['' => '«' . $section['title'] . '» (верхний уровень)'] + $categories, $v, $errors) ?>
  <?= f_textarea('description', 'Краткое описание (необязательно)', $v, $errors, 3) ?>
  <div class="row">
    <?= f_text('slug', 'Адрес (slug)', $v, $errors, ['placeholder' => 'сформируется из названия']) ?>
    <?= f_text('sort', 'Сортировка', $v + ['sort' => 100], $errors, ['type' => 'number']) ?>
  </div>
  <?= f_check('published', 'Опубликована', $v) ?>
  <div class="progress" hidden><div class="progress-bar"></div><span class="progress-text"></span></div>
  <div class="actions"><button class="btn btn--primary">Сохранить</button><a class="btn" href="<?= e($back) ?>">К каталогу</a></div>
</form>
<?php endif ?>
