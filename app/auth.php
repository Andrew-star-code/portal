<?php
declare(strict_types=1);

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW_SEC = 900;

function admin_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('vp_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (base_path() ?: '') . '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();

    // Разлогиниваем после 8 часов бездействия.
    if (isset($_SESSION['uid'], $_SESSION['seen']) && time() - $_SESSION['seen'] > 8 * 3600) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['seen'] = time();
}

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = isset($_SESSION['uid'])
            ? db_one('SELECT id, login FROM users WHERE id = ?', [$_SESSION['uid']])
            : null;
    }
    return $user;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('/admin/login');
    }
    return $u;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function login_blocked(): bool
{
    db_exec('DELETE FROM login_attempts WHERE at < ?', [time() - LOGIN_WINDOW_SEC]);
    $n = (int)db_one('SELECT COUNT(*) AS n FROM login_attempts WHERE ip = ?', [client_ip()])['n'];
    return $n >= LOGIN_MAX_ATTEMPTS;
}

function attempt_login(string $login, string $password): bool
{
    $u = db_one('SELECT id, password_hash FROM users WHERE login = ?', [$login]);
    // Проверяем хеш и для несуществующего пользователя, чтобы не выдавать его наличие по времени ответа.
    $hash = $u['password_hash'] ?? dummy_hash();
    $ok = password_verify($password, $hash) && $u;
    if (!$ok) {
        db_exec('INSERT INTO login_attempts (ip, at) VALUES (?, ?)', [client_ip(), time()]);
        return false;
    }
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $u['id']]);
    }
    db_exec('DELETE FROM login_attempts WHERE ip = ?', [client_ip()]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$u['id'];
    unset($_SESSION['csrf']);
    return true;
}

function dummy_hash(): string
{
    $h = db_one("SELECT value FROM settings WHERE key = '_dummy_hash'")['value'] ?? null;
    if (!$h) {
        $h = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        db_exec("INSERT OR REPLACE INTO settings (key, value) VALUES ('_dummy_hash', ?)", [$h]);
    }
    return $h;
}

function logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
    session_destroy();
}

function password_problem(string $password): ?string
{
    return mb_strlen($password) < 10 ? 'Пароль должен быть не короче 10 символов.' : null;
}
