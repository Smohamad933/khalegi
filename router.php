<?php
/**
 * روتر سرور داخلی PHP (فقط برای توسعه‌ی محلی):
 *   php -S 0.0.0.0:8000 router.php
 * روی هاست واقعی (Apache/Nginx) نیازی به این فایل نیست.
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// جلوگیری از دسترسی مستقیم به پوشه‌های داخلی
if (preg_match('~^/(app|data)(/|$)~', $path)) {
    http_response_code(403);
    exit('Forbidden');
}
if ($path !== '/' && is_file($file)) {
    return false; // فایل استاتیک را خود سرور سرو کند
}
if (is_dir($file) && is_file(rtrim($file, '/') . '/index.php')) {
    require rtrim($file, '/') . '/index.php';
    return true;
}
http_response_code(404);
echo '404';
