<?php
/**
 * تولید پویا‌ی @font-face برای فونت‌های آپلودشده از پنل مدیریت.
 * خروجی: CSS — در صفحه‌ی اصلی و پنل لینک می‌شود.
 */
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=300');

$fonts    = rows('SELECT * FROM fonts ORDER BY family, weight');
$family   = setting('font_family', 'abar');
$fallback = setting('font_fallback', 'Vazirmatn');

$formats = ['woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype'];

echo "/* فونت‌های سایت — تولید خودکار */\n";
foreach ($fonts as $f) {
    $url = upload_url($f['file']);
    $fmt = $formats[$f['format']] ?? $f['format'];
    $weight = preg_match('/^\d+(\s+\d+)?$/', $f['weight']) ? $f['weight'] : '400';
    printf(
        "@font-face{font-family:'%s';src:url('%s') format('%s');font-weight:%s;font-style:%s;font-display:swap;}\n",
        addslashes($f['family']), $url, $fmt, $weight, $f['style'] === 'italic' ? 'italic' : 'normal'
    );
}

/*
 * اگر فونت اصلی (مثلاً abar) هنوز آپلود نشده باشد، مرورگر به‌طور خودکار
 * سراغ فونت بعدی در font-family (Vazirmatn) می‌رود؛ بنابراین همیشه نام هر دو را می‌نویسیم.
 */
printf(":root{--font:'%s','%s','Segoe UI',Tahoma,sans-serif;}\n", addslashes($family), addslashes($fallback));
