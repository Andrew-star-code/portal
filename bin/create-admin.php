<?php
// Создание администратора или сброс его пароля.
// Использование: php bin/create-admin.php <логин>
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Только для командной строки.\n");
}

require dirname(__DIR__) . '/app/bootstrap.php';

$login = $argv[1] ?? '';
if (!preg_match('~^[a-zA-Z0-9._-]{3,40}$~', $login)) {
    fwrite(STDERR, "Использование: php bin/create-admin.php <логин>\nЛогин: 3–40 латинских букв, цифр, точек, дефисов.\n");
    exit(1);
}

function read_secret(string $prompt): string
{
    echo $prompt;
    $hide = DIRECTORY_SEPARATOR === '/' && (function_exists('stream_isatty') ? stream_isatty(STDIN) : (function_exists('posix_isatty') && posix_isatty(STDIN)));
    if ($hide) {
        shell_exec('stty -echo');
    }
    $line = fgets(STDIN);
    if ($hide) {
        shell_exec('stty echo');
        echo "\n";
    }
    return rtrim((string)$line, "\r\n");
}

$pass = read_secret('Пароль: ');
if ($err = password_problem($pass)) {
    fwrite(STDERR, $err . "\n");
    exit(1);
}
if ($pass !== read_secret('Повторите пароль: ')) {
    fwrite(STDERR, "Пароли не совпадают.\n");
    exit(1);
}

$hash = password_hash($pass, PASSWORD_DEFAULT);
if (db_one('SELECT id FROM users WHERE login = ?', [$login])) {
    db_exec('UPDATE users SET password_hash = ? WHERE login = ?', [$hash, $login]);
    echo "Пароль пользователя «{$login}» обновлён.\n";
} else {
    db_exec('INSERT INTO users (login, password_hash, created_at) VALUES (?, ?, ?)', [$login, $hash, now()]);
    echo "Администратор «{$login}» создан.\n";
}
