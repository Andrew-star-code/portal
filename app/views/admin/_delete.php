<?php /** @var string $action @var string $what */ ?>
<form method="post" action="<?= e(url($action)) ?>" class="inline" data-confirm="Удалить <?= e($what) ?>? Действие необратимо.">
  <?= csrf_field() ?><button class="btn btn--danger">Удалить</button>
</form>
