<?php /** @var array $v @var array $errors */ ?>
<div class="head"><h1>Настройки сайта</h1></div>

<form method="post" enctype="multipart/form-data" class="form form--narrow" action="<?= e(url('/admin/settings')) ?>">
  <?= csrf_field() ?>
  <?= f_text('site_name', 'Название сайта (заголовок вкладки браузера)', $v, $errors, ['required' => true]) ?>

  <fieldset class="fs">
    <legend>Логотип в шапке</legend>
    <div class="logo-preview" data-logo-preview
         data-diamond="<?= e(LOGO_DIAMOND_SVG) ?>"
         data-icon-url="<?= e($v['logo_icon'] ? url($v['logo_icon']) : '') ?>"
         data-image-url="<?= e($v['logo_image'] ? url($v['logo_image']) : '') ?>">
      <span class="lp-caption">Предпросмотр</span>
      <div class="lp-bar"><span class="lp-logo"></span><span class="lp-nav">Бюро ▾ &nbsp; Продукция ▾ &nbsp; Новости</span></div>
    </div>

    <?= f_select('logo_mode', 'Вид логотипа', LOGO_MODES, $v, $errors) ?>
    <div data-show-if="logo_mode:text" class="fs-group">
      <div class="row row--logo">
        <?= f_text('logo_left', 'Текст слева', $v, $errors, ['maxlength' => 40]) ?>
        <?= f_select('logo_icon_mode', 'Значок посередине', LOGO_ICON_MODES, $v, $errors) ?>
        <?= f_text('logo_right', 'Текст справа', $v, $errors, ['maxlength' => 40]) ?>
      </div>
      <div data-show-if="logo_icon_mode:image">
        <?= f_image('logo_icon', 'Картинка значка', $v, $errors, ['PNG', 'SVG', 'WebP']) ?>
        <p class="f-hint">Значок выводится высотой 32 пикселя между текстами. Лучше SVG или PNG с прозрачным фоном, высотой от 64 пикселей.</p>
      </div>
      <p class="f-hint">Любую часть можно оставить пустой: например, только значок и текст справа.</p>
    </div>
    <div data-show-if="logo_mode:image">
      <?= f_image('logo_image', 'Картинка логотипа', $v, $errors, ['PNG', 'SVG', 'WebP', 'JPG']) ?>
      <p class="f-hint">Лучше всего SVG или PNG с прозрачным фоном. В шапке логотип выводится высотой 40 пикселей,
        поэтому для чёткости на экранах высокой плотности загружайте PNG высотой от 80 пикселей. Ширина — любая, до 240 пикселей на экране.</p>
    </div>
  </fieldset>

  <fieldset class="fs">
    <legend>Значок вкладки браузера (favicon)</legend>
    <?= f_image('favicon', 'Значок', $v, $errors, ['PNG', 'SVG', 'ICO']) ?>
    <p class="f-hint">Квадратная картинка: SVG или PNG от 64×64 пикселей (браузер сам уменьшит её до 16–32 пикселей).
      Если не загружать — останется стандартный ромб. Браузеры кэшируют значок: если после замены виден старый, обновите страницу с Ctrl+F5.</p>
  </fieldset>

  <?= f_textarea('address', 'Адрес (в подвале)', $v, $errors, 3) ?>
  <div class="row">
    <?= f_text('phone', 'Телефон', $v, $errors) ?>
    <?= f_text('email', 'E-mail', $v, $errors, ['type' => 'email']) ?>
  </div>
  <div class="actions"><button class="btn btn--primary">Сохранить</button></div>
</form>
