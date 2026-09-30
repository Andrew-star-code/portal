<?php
// Настройки по умолчанию. Не редактируйте этот файл —
// переопределяйте нужные ключи в config.php в корне проекта (см. config.example.php).
return [
    'debug'         => false,
    'timezone'      => 'Europe/Moscow',
    'db_path'       => ROOT . '/data/portal.sqlite',
    // URL-префикс, если сайт открыт из подкаталога (например '/portal').
    // null — определить автоматически.
    'base_url'      => null,
    'uploads_dir'   => ROOT . '/public/uploads',
    'upload_max_mb' => 8,     // картинки
    'file_max_mb'   => 600,   // инструкции (PDF, видео); лимиты PHP см. public/.user.ini
    'news_per_page' => 10,
    // Ключ для первичного создания администратора через браузер (/admin/setup).
    // Пустая строка — веб-установка отключена, используйте bin/create-admin.php.
    'setup_key'     => '',
];
