<?php
/**
 * دانلود کل سورس سایت به‌صورت ZIP
 * - دکمه‌ی آن کنار «ورود مدیریت» در پاصفحه نمایش داده می‌شود و از پنل → تنظیمات قابل خاموش کردن است.
 * - فایل‌های حساس (دیتابیس، نشست‌ها، .git) در ZIP قرار نمی‌گیرند؛ فایل‌های آپلودشده به‌صورت اختیاری
 *   (فقط برای مدیرِ واردشده با ?with_uploads=1) اضافه می‌شوند.
 */
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$isAdmin = auth_user() !== null;
if (setting('show_download', '1') !== '1' && !$isAdmin) {
    http_response_code(404);
    exit('این امکان غیرفعال است.');
}
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('افزونه‌ی ZipArchive روی سرور فعال نیست.');
}

$withUploads = $isAdmin && !empty($_GET['with_uploads']);
$root = realpath(APP_ROOT) ?: APP_ROOT;

// مسیرهایی که هرگز داخل ZIP نمی‌روند
$skipDirs  = ['.git', 'data', 'node_modules', '.idea', '.vscode'];
$skipFiles = ['.DS_Store', 'Thumbs.db'];
if (!$withUploads) {
    $skipDirs[] = 'uploads';
}

// کش ۱۰ دقیقه‌ای تا درخواست‌های پیاپی سرور را مشغول نکنند
$cacheFile = dirname($GLOBALS['config']['db_path']) . '/cache-source' . ($withUploads ? '-uploads' : '') . '.zip';
if (is_file($cacheFile) && filemtime($cacheFile) > time() - 600) {
    send_zip($cacheFile, $withUploads);
}

$tmp = tempnam(sys_get_temp_dir(), 'site');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit('ساخت فایل ZIP ممکن نشد.');
}

$prefix = 'khalegi-brochure/';
$it = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        function (SplFileInfo $f) use ($root, $skipDirs, $skipFiles): bool {
            $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
            $top = explode('/', $rel)[0];
            if ($f->isDir()) {
                return !in_array($top, $skipDirs, true);
            }
            return !in_array($f->getFilename(), $skipFiles, true);
        }
    ),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($it as $f) {
    /** @var SplFileInfo $f */
    $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($root))), '/');
    if ($f->isDir()) {
        $zip->addEmptyDir($prefix . $rel);
    } elseif ($rel === 'app/config.php') {
        // هش رمز عبور مدیر در نسخه‌ی دانلودی قرار نمی‌گیرد؛ نصب تازه، صفحه‌ی «ساخت حساب مدیر» را نشان می‌دهد
        $cfg = (string)file_get_contents($f->getPathname());
        $cfg = preg_replace("/('password_hash'\s*=>\s*)'[^']*'/", "$1''", $cfg) ?? $cfg;
        $zip->addFromString($prefix . $rel, $cfg);
    } else {
        $zip->addFile($f->getPathname(), $prefix . $rel);
    }
}
// پوشه‌های خالیِ لازم برای اجرا
$zip->addEmptyDir($prefix . 'data');
$zip->addFromString($prefix . 'data/.gitkeep', '');
$zip->addFromString($prefix . 'data/.htaccess', "Require all denied\n");
if (!$withUploads) {
    $zip->addEmptyDir($prefix . 'uploads');
    $zip->addFromString($prefix . 'uploads/.gitkeep', '');
    if (is_file($root . '/uploads/.htaccess')) {
        $zip->addFile($root . '/uploads/.htaccess', $prefix . 'uploads/.htaccess');
    }
}
$zip->close();
if (@rename($tmp, $cacheFile) || (@copy($tmp, $cacheFile) && @unlink($tmp))) {
    send_zip($cacheFile, $withUploads);
}
send_zip($tmp, $withUploads, true);

function send_zip(string $path, bool $withUploads, bool $deleteAfter = false): never
{
    $name = 'khalegi-brochure-' . date('Ymd', filemtime($path) ?: time()) . ($withUploads ? '-with-uploads' : '') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . (string)filesize($path));
    header('Cache-Control: no-store');
    readfile($path);
    if ($deleteAfter) {
        @unlink($path);
    }
    exit;
}
