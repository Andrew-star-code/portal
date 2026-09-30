<?php /** @var array $section @var array $trail @var bool $linkLast */
$links = [[url('/'), 'Главная'], [catalog_url($section), $section['title']]];
foreach ($trail as $i => $c) {
    $links[] = [catalog_url($section, array_slice($trail, 0, $i + 1)), $c['title']];
}
if (!$linkLast) {
    $last = array_pop($links);
}
?>
<nav class="crumbs" aria-label="Навигация">
  <?php foreach ($links as $i => [$href, $title]): ?><?= $i ? ' / ' : '' ?><a href="<?= e($href) ?>"><?= e($title) ?></a><?php endforeach ?>
  <?php if (!$linkLast): ?> / <span><?= e($last[1]) ?></span><?php endif ?>
</nav>
