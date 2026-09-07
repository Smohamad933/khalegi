<?php
declare(strict_types=1);

/** فرار از HTML */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** آدرس نسبی به ریشه‌ی سایت */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return (BASE_URL === '' ? '' : BASE_URL) . '/' . $path;
}

/** آدرس یک فایل آپلودشده (یا مسیر خالی) */
function upload_url(?string $file): string
{
    if (!$file) {
        return '';
    }
    if (preg_match('~^https?://~i', $file)) {
        return $file;
    }
    return url($GLOBALS['config']['uploads_url'] . '/' . $file);
}

/**
 * آدرس یک رسانه: می‌تواند فایل آپلودشده، مسیر داخل assets یا آدرس کامل http باشد.
 */
function media_url(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $value) || str_starts_with($value, 'data:')) {
        return $value;
    }
    if (str_starts_with($value, 'assets/')) {
        return url($value);
    }
    return upload_url($value);
}

/** آدرس فایل استاتیک با نسخه‌گذاری برای کش */
function asset(string $path): string
{
    $file = APP_ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . substr(md5((string)filemtime($file)), 0, 8) : '';
    return url($path) . $v;
}

/** تبدیل اعداد لاتین به فارسی */
function fa_num(string|int|float|null $s): string
{
    return strtr((string)$s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}

/** تبدیل متن چندخطی به آرایه‌ی خطوط غیرخالی */
function lines(?string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", (string)$text);
    return array_values(array_filter(array_map('trim', explode("\n", $text)), fn($l) => $l !== ''));
}

/** ریدایرکت */
function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/** پیام فلش (یک‌بار مصرف) */
function flash(string $type, ?string $msg = null): ?array
{
    if ($msg !== null) {
        $_SESSION['_flash'] = ['type' => $type, 'msg' => $msg];
        return null;
    }
    return null;
}

function flash_get(): ?array
{
    if (!empty($_SESSION['_flash'])) {
        $f = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $f;
    }
    return null;
}

/** توکن CSRF */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $ok = isset($_POST['_csrf'], $_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], (string)$_POST['_csrf']);
    if (!$ok) {
        http_response_code(419);
        exit('درخواست نامعتبر است (CSRF). لطفاً صفحه را دوباره باز کنید.');
    }
}

/** ورودی POST به‌صورت رشته‌ی trim شده */
function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function post_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) && is_numeric($_POST[$key]) ? (int)$_POST[$key] : $default;
}

/**
 * ذخیره‌ی فایل آپلودشده. در صورت موفقیت نام فایل جدید و در غیر این صورت null برمی‌گرداند.
 * $kind: image | audio | font
 */
function handle_upload(string $field, string $kind, ?string &$error = null): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = 'خطا در آپلود فایل (کد ' . $f['error'] . '). احتمالاً حجم فایل بیش از حد مجاز است.';
        return null;
    }
    $maxBytes = (int)$GLOBALS['config']['max_upload_mb'] * 1024 * 1024;
    if ($f['size'] > $maxBytes) {
        $error = 'حجم فایل بیشتر از حد مجاز است.';
        return null;
    }

    if ($kind === 'font') {
        // فونت‌ها: MIME گزارش‌شده توسط مرورگر/سرور قابل اتکا نیست؛ امضای باینری فایل را بررسی می‌کنیم
        $ext = font_ext_by_signature($f['tmp_name']);
        if ($ext === null) {
            $error = 'فایل فونت معتبر نیست؛ فقط فایل‌های woff2 / woff / ttf / otf پذیرفته می‌شوند.';
            return null;
        }
    } else {
        $allowed = [
            'image' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => 'svg'],
            'audio' => ['audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a', 'audio/aac' => 'aac', 'video/mp4' => 'mp4'],
        ][$kind] ?? [];

        $mime = '';
        if (class_exists('finfo')) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string)$fi->file($f['tmp_name']);
        }
        if ($mime === '' || $mime === 'application/octet-stream') {
            $mime = (string)($f['type'] ?? '');
        }
        if (!isset($allowed[$mime])) {
            $error = 'نوع فایل مجاز نیست (' . e($mime) . ').';
            return null;
        }
        $ext = $allowed[$mime];
    }
    $dir  = rtrim($GLOBALS['config']['uploads_dir'], '/');
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        $error = 'ذخیره‌ی فایل ممکن نشد. دسترسی نوشتن پوشه‌ی uploads را بررسی کنید.';
        return null;
    }
    return $name;
}

/** تشخیص نوع فایل فونت از روی امضای باینری (۴ بایت اول). در صورت نامعتبر بودن null */
function font_ext_by_signature(string $path): ?string
{
    $h = fopen($path, 'rb');
    if (!$h) {
        return null;
    }
    $head = (string)fread($h, 4);
    fclose($h);
    return match ($head) {
        'wOF2'             => 'woff2',
        'wOFF'             => 'woff',
        "\x00\x01\x00\x00", 'true' => 'ttf',
        'OTTO'             => 'otf',
        default            => null,
    };
}

/** حذف امن فایل آپلودشده */
function delete_upload(?string $file): void
{
    if (!$file || preg_match('~^https?://~i', $file) || str_contains($file, '/') || str_contains($file, '..')) {
        return;
    }
    $p = rtrim($GLOBALS['config']['uploads_dir'], '/') . '/' . $file;
    if (is_file($p)) {
        @unlink($p);
    }
}

/** رندر قالب با متغیرها */
function view(string $file, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    require APP_ROOT . '/app/views/' . $file . '.php';
}
