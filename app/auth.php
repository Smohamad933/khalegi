<?php
declare(strict_types=1);

/** شروع نشست (فقط در صورت نیاز) */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name($GLOBALS['config']['session_name']);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => (BASE_URL === '' ? '/' : BASE_URL . '/'),
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

function auth_user(): ?array
{
    session_boot();
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $user = row('SELECT id, username FROM admins WHERE id = ?', [(int)$_SESSION['admin_id']]);
        if (!$user) {
            unset($_SESSION['admin_id']);
            return null;
        }
    }
    return $user;
}

function auth_attempt(string $username, string $password): bool
{
    session_boot();
    $u = row('SELECT * FROM admins WHERE username = ?', [$username]);
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$u['id'];
        return true;
    }
    // تأخیر کوچک برای کاهش حملات brute-force
    usleep(300000);
    return false;
}

function auth_logout(): void
{
    session_boot();
    $_SESSION = [];
    session_destroy();
}

/** الزام ورود برای صفحات پنل */
function require_login(): array
{
    $u = auth_user();
    if (!$u) {
        redirect(url('admin/login.php'));
    }
    return $u;
}
