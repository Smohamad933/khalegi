<?php
declare(strict_types=1);

/** شروع نشست (فقط در صورت نیاز) */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name($GLOBALS['config']['session_name']);
        $days  = max(1, (int)($GLOBALS['config']['session_days'] ?? 14));
        $https = is_https();

        /*
         * محل ذخیره‌ی نشست‌ها: پوشه‌ی data/sessions داخل خود پروژه.
         * روی بسیاری از هاست‌های اشتراکی، مسیر پیش‌فرض نشست PHP قابل نوشتن نیست و نتیجه‌اش این است که
         * نشست بین دو درخواست گم می‌شود و فرم ورود با خطای CSRF رد می‌شود. این تنظیم آن مشکل را حل می‌کند.
         */
        $sessDir = dirname($GLOBALS['config']['db_path']) . '/sessions';
        if (!is_dir($sessDir)) {
            @mkdir($sessDir, 0770, true);
        }
        if (is_dir($sessDir) && is_writable($sessDir)) {
            session_save_path($sessDir);
        }

        /*
         * کوکی نشست:
         *  - path = ریشه‌ی پروژه (در زیرپوشه هم کار می‌کند)
         *  - روی HTTPS: SameSite=None + Secure تا پنل داخل iframe/پیش‌نمایش هم کار کند
         *  - روی HTTP: SameSite=Lax (مرورگرها SameSite=None را بدون Secure نمی‌پذیرند)
         */
        session_set_cookie_params([
            'lifetime' => $days * 86400,
            'path'     => (BASE_URL === '' ? '/' : BASE_URL . '/'),
            'httponly' => true,
            'samesite' => $https ? 'None' : 'Lax',
            'secure'   => $https,
        ]);
        ini_set('session.gc_maxlifetime', (string)($days * 86400));
        ini_set('session.cookie_lifetime', (string)($days * 86400));
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }
}

/** آیا درخواست فعلی روی HTTPS است؟ (با پشتیبانی از پروکسی معکوس) */
function is_https(): bool
{
    $forced = $GLOBALS['config']['force_https'] ?? null;
    if (is_bool($forced)) {
        return $forced;
    }
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((string)($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    // پشت پروکسی معکوس / کلادفلر / پیش‌نمایش
    if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
        return true;
    }
    if (strtolower((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on' || strtolower((string)($_SERVER['HTTP_FRONT_END_HTTPS'] ?? '')) === 'on') {
        return true;
    }
    if (str_contains((string)($_SERVER['HTTP_CF_VISITOR'] ?? ''), 'https')) {
        return true;
    }
    // سرویس‌های پیش‌نمایش که همیشه TLS را در لبه پایان می‌دهند
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    return (bool)preg_match('/\.(e2b\.app|e2b\.dev|ngrok(-free)?\.app|trycloudflare\.com|loca\.lt)$/', explode(':', $host)[0]);
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
    // نام کاربری حساس به بزرگی/کوچکی حروف نیست
    $u = row('SELECT * FROM admins WHERE lower(username) = lower(?)', [$username]);
    if ($u && password_verify($password, $u['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$u['id'];
        $_SESSION['_csrf']    = bin2hex(random_bytes(16)); // توکن تازه پس از ورود
        if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
            q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), (int)$u['id']]);
        }
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
        redirect(admins_exist() ? url('admin/login.php') : url('admin/setup.php'));
    }
    return $u;
}

/** آیا حداقل یک حساب مدیر ساخته شده است؟ */
function admins_exist(): bool
{
    return (int)scalar('SELECT COUNT(*) FROM admins') > 0;
}

/**
 * بازیابی اضطراری رمز عبور (وقتی به پنل دسترسی ندارید):
 * فایلی به نام data/reset-admin.txt با محتوای   نام‌کاربری:رمز‌جدید   بسازید (با FTP یا فایل‌منیجر هاست).
 * با اولین بازکردن صفحه‌ی ورود، حساب ساخته/به‌روزرسانی و فایل حذف می‌شود.
 */
function apply_emergency_reset(): ?string
{
    $file = dirname($GLOBALS['config']['db_path']) . '/reset-admin.txt';
    if (!is_file($file)) {
        return null;
    }
    $line = trim((string)file_get_contents($file));
    @unlink($file);
    if (!str_contains($line, ':')) {
        return 'فایل reset-admin.txt باید به شکل  نام‌کاربری:رمز‌جدید  باشد.';
    }
    [$user, $pass] = explode(':', $line, 2);
    $user = trim($user);
    $pass = trim($pass);
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,32}$/', $user) || mb_strlen($pass) < 8) {
        return 'نام کاربری یا رمز عبور در فایل بازیابی نامعتبر است (رمز حداقل ۸ کاراکتر).';
    }
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    if (row('SELECT id FROM admins WHERE lower(username) = lower(?)', [$user])) {
        q('UPDATE admins SET password_hash = ? WHERE lower(username) = lower(?)', [$hash, $user]);
    } else {
        q('INSERT INTO admins(username, password_hash) VALUES(?, ?)', [$user, $hash]);
    }
    return 'رمز عبور «' . $user . '» با موفقیت بازیابی شد؛ اکنون وارد شوید.';
}
