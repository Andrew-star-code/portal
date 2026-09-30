<?php
/** @var string $title @var string $active @var string $content @var string $bodyCls */
$menu = site_menu();
$siteName = setting('site_name');
?><!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e($title !== '' ? "$title — $siteName" : $siteName) ?></title>
<link rel="preload" href="<?= e(url('assets/fonts/roboto-cyrillic.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('assets/fonts/roboto.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<?= favicon_tag() ?>
</head>
<body class="<?= e($bodyCls) ?>">

<header class="header">
  <?= logo_html() ?>
  <button class="burger" id="burger" aria-label="Меню" aria-expanded="false" aria-controls="nav"><span></span><span></span><span></span></button>
  <ul class="nav" id="nav">
    <?php foreach ($menu as $s): $cls = 'nav-btn' . ($active === $s['slug'] ? ' is-active' : ''); ?>
    <?php if ($s['type'] === 'dropdown'): if (!$s['pages']) continue; ?>
    <li><button class="<?= $cls ?>" aria-expanded="false"><?= e($s['title']) ?><span class="caret"></span></button>
      <ul class="dropdown">
        <?php foreach ($s['pages'] as $p): ?>
        <li><a href="<?= e(url("/{$s['slug']}/{$p['slug']}")) ?>"><?= e($p['title']) ?></a></li>
        <?php endforeach ?>
      </ul></li>
    <?php else: $external = $s['type'] === 'link' && preg_match('~^(https?:)?//~i', $s['link']); ?>
    <li><a class="<?= $cls ?>" href="<?= e(section_href($s)) ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?>><?= e($s['title']) ?></a></li>
    <?php endif ?>
    <?php endforeach ?>
    <li><a class="nav-btn<?= $active === 'news' ? ' is-active' : '' ?>" href="<?= e(url('/news')) ?>">Новости</a></li>
  </ul>
</header>
<script>
// Сворачиваем меню в «бургер», если вкладки не помещаются в строку (их число задаётся в админке).
(function () {
  var h = document.querySelector('.header'), nav = document.getElementById('nav'), logo = h.querySelector('.logo');
  function fit() {
    h.classList.remove('collapsed');
    if (innerWidth <= 820 || nav.scrollWidth + logo.offsetWidth + 48 > h.clientWidth) h.classList.add('collapsed');
  }
  fit();
  addEventListener('resize', fit);
  addEventListener('load', fit); // ширина картинки-логотипа известна только после загрузки
  if (document.fonts) document.fonts.ready.then(fit);
})();
</script>

<?= $content ?>

<footer class="footer">
  <div class="footer-in">
    <span>© <?= date('Y') ?> <?= e(setting('site_name')) ?></span>
    <address>
      <?= e(preg_replace('~\s*\R\s*~u', ' ', setting('address'))) ?>
      <?php if ($phone = setting('phone')): ?> · <a href="tel:<?= e(preg_replace('~[^\d+]~', '', $phone)) ?>"><?= e($phone) ?></a><?php endif ?>
      <?php if ($email = setting('email')): ?> · <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif ?>
    </address>
  </div>
</footer>

<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
</body>
</html>
