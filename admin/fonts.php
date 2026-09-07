<?php
/**
 * مدیریت فونت سایت: آپلود فایل فونت (مثلاً abar) و انتخاب نام خانواده‌ی فونت
 */
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'settings') {
        $family = post('font_family');
        if ($family === '' || !preg_match('/^[\w\s\-]{1,60}$/u', $family)) {
            flash('error', 'نام خانواده‌ی فونت نامعتبر است.');
            redirect('fonts.php');
        }
        setting_set('font_family', $family);
        setting_set('font_fallback', post('font_fallback') ?: 'Vazirmatn');
        flash('success', 'تنظیمات فونت ذخیره شد.');
        redirect('fonts.php');
    }

    if ($action === 'upload') {
        $family = post('family') ?: setting('font_family', 'abar');
        $files  = $_FILES['files'] ?? null;
        $count  = 0;
        $errors = [];
        if ($files && is_array($files['name'])) {
            foreach ($files['name'] as $i => $n) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $_FILES['_one'] = [
                    'name' => $n, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i], 'size' => $files['size'][$i],
                ];
                // اگر فایل ZIP باشد، فونت‌های داخل آن استخراج می‌شوند
                if (strtolower(pathinfo((string)$n, PATHINFO_EXTENSION)) === 'zip' && class_exists('ZipArchive')) {
                    foreach (extract_fonts_from_zip($files['tmp_name'][$i], $errors) as [$innerName, $savedName]) {
                        register_font($family, $innerName, $savedName);
                        $count++;
                    }
                    continue;
                }
                $err = null;
                $saved = handle_upload('_one', 'font', $err);
                if (!$saved) {
                    if ($err) {
                        $errors[] = $n . ': ' . $err;
                    }
                    continue;
                }
                register_font($family, (string)$n, $saved);
                $count++;
            }
        }
        if ($count && setting('font_family') !== $family && post('make_default') === '1') {
            setting_set('font_family', $family);
        }
        flash($errors ? 'error' : 'success', ($count ? fa_num($count) . ' فایل فونت اضافه شد. ' : '') . implode(' | ', $errors));
        redirect('fonts.php');
    }

    if ($action === 'delete') {
        $f = row('SELECT * FROM fonts WHERE id = ?', [post_int('id')]);
        if ($f) {
            delete_upload($f['file']);
            q('DELETE FROM fonts WHERE id = ?', [(int)$f['id']]);
            flash('success', 'فونت حذف شد.');
        }
        redirect('fonts.php');
    }

    if ($action === 'update') {
        $id = post_int('id');
        if (row('SELECT id FROM fonts WHERE id = ?', [$id])) {
            $w = post('weight');
            if (!preg_match('/^\d{3}(\s+\d{3})?$/', $w)) {
                $w = '400';
            }
            q('UPDATE fonts SET weight = ?, style = ?, family = ? WHERE id = ?', [$w, post('style') === 'italic' ? 'italic' : 'normal', post('family') ?: setting('font_family'), $id]);
            flash('success', 'به‌روزرسانی شد.');
        }
        redirect('fonts.php');
    }
}

/** ثبت رکورد فونت با حدس وزن/استایل از نام فایل (یا وزن انتخاب‌شده در فرم) */
function register_font(string $family, string $origName, string $savedFile): void
{
    $weight = post('weight');
    if ($weight === '' || $weight === 'auto') {
        $weight = guess_font_weight($origName);
    }
    $style = stripos($origName, 'italic') !== false ? 'italic' : 'normal';
    q('INSERT INTO fonts(family, weight, style, file, format) VALUES(?,?,?,?,?)', [
        $family, $weight, $style, $savedFile, strtolower(pathinfo($savedFile, PATHINFO_EXTENSION)),
    ]);
}

/** استخراج فایل‌های فونت از یک ZIP؛ خروجی: [[نام اصلی, نام ذخیره‌شده], ...] */
function extract_fonts_from_zip(string $zipPath, array &$errors): array
{
    $out = [];
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        $errors[] = 'فایل ZIP باز نشد.';
        return $out;
    }
    $dir = rtrim($GLOBALS['config']['uploads_dir'], '/');
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        $base = basename($name);
        if (str_starts_with($base, '.') || str_starts_with($name, '__MACOSX')) {
            continue;
        }
        if (!in_array(strtolower(pathinfo($base, PATHINFO_EXTENSION)), ['woff2', 'woff', 'ttf', 'otf'], true)) {
            continue;
        }
        $data = $zip->getFromIndex($i);
        if ($data === false || strlen($data) > (int)$GLOBALS['config']['max_upload_mb'] * 1024 * 1024) {
            continue;
        }
        $tmp = tempnam(sys_get_temp_dir(), 'font');
        file_put_contents($tmp, $data);
        $ext = font_ext_by_signature($tmp);
        if ($ext === null) {
            @unlink($tmp);
            $errors[] = $base . ': فایل فونت معتبر نیست.';
            continue;
        }
        $saved = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (@rename($tmp, $dir . '/' . $saved) || (@copy($tmp, $dir . '/' . $saved) && @unlink($tmp))) {
            @chmod($dir . '/' . $saved, 0644);
            $out[] = [$base, $saved];
        }
    }
    $zip->close();
    if (!$out && !$errors) {
        $errors[] = 'هیچ فایل فونتی (woff2/woff/ttf/otf) داخل ZIP پیدا نشد.';
    }
    return $out;
}

function guess_font_weight(string $name): string
{
    $n = strtolower($name);
    $map = [
        'thin' => '100', 'hairline' => '100', 'extralight' => '200', 'ultralight' => '200', 'light' => '300',
        'regular' => '400', 'normal' => '400', 'book' => '400', 'medium' => '500', 'semibold' => '600', 'demibold' => '600',
        'extrabold' => '800', 'ultrabold' => '800', 'heavy' => '900', 'black' => '900', 'bold' => '700',
    ];
    if (preg_match('/\[?wght\]?|variable|vf\b/i', $n)) {
        return '100 900';
    }
    foreach ($map as $k => $w) {
        if (str_contains(str_replace(['-', '_', ' '], '', $n), $k)) {
            return $w;
        }
    }
    return '400';
}

$fonts    = rows('SELECT * FROM fonts ORDER BY family, weight, style');
$families = array_values(array_unique(array_column($fonts, 'family')));
$current  = setting('font_family', 'abar');
$hasCurrent = in_array($current, $families, true);

admin_header('فونت سایت', 'fonts');
?>
<div class="card">
  <h2>وضعیت فعلی</h2>
  <p>فونت اصلی سایت: <code><?= e($current) ?></code>
    <?php if ($hasCurrent): ?>
      <span class="badge badge--ok">فعال — <?= fa_num(count(array_filter($fonts, fn($f) => $f['family'] === $current))) ?> فایل</span>
    <?php else: ?>
      <span class="badge badge--warn">هنوز فایلی برای این فونت آپلود نشده؛ سایت فعلاً با <code><?= e(setting('font_fallback', 'Vazirmatn')) ?></code> نمایش داده می‌شود.</span>
    <?php endif; ?>
  </p>
  <p class="muted">به محض آپلود فایل‌های فونت با همین نام خانواده، کل سایت و پنل به‌طور خودکار با آن نمایش داده می‌شود.</p>
  <p class="font-sample" style="font-family:'<?= e($current) ?>','<?= e(setting('font_fallback', 'Vazirmatn')) ?>',sans-serif">نمونه‌ی متن: کنسرت ارکستر سازهای ایرانی به یاد استاد روح‌الله خالقی — ۱۲۳۴۵۶۷۸۹۰</p>
</div>

<form method="post" enctype="multipart/form-data" class="card form">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="upload">
  <h2>آپلود فایل فونت</h2>
  <div class="grid-3">
    <label>نام خانواده‌ی فونت<input type="text" name="family" value="<?= e($current) ?>" dir="ltr" required></label>
    <label>وزن
      <select name="weight">
        <option value="auto">تشخیص خودکار از نام فایل</option>
        <option value="100 900">متغیر (Variable — همه‌ی وزن‌ها)</option>
        <?php foreach (['100' => 'Thin', '200' => 'ExtraLight', '300' => 'Light', '400' => 'Regular', '500' => 'Medium', '600' => 'SemiBold', '700' => 'Bold', '800' => 'ExtraBold', '900' => 'Black'] as $w => $l): ?>
          <option value="<?= $w ?>"><?= $w ?> — <?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>فایل‌ها (woff2 / woff / ttf / otf یا ZIP — چندتایی)<input type="file" name="files[]" accept=".woff2,.woff,.ttf,.otf,.zip" multiple required></label>
  </div>
  <label class="check"><input type="checkbox" name="make_default" value="1" checked> این خانواده فونت اصلی سایت باشد</label>
  <div class="form-actions"><button class="btn btn--primary" type="submit">آپلود فونت</button></div>
  <p class="hint">پیشنهاد: برای هر وزن (Regular، Bold و …) یک فایل جداگانه آپلود کنید یا یک فایل Variable بارگذاری کنید. فرمت woff2 سبک‌ترین است.</p>
</form>

<form method="post" class="card form">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="settings">
  <h2>انتخاب فونت اصلی</h2>
  <div class="grid-2">
    <label>خانواده‌ی فونت اصلی
      <input type="text" name="font_family" value="<?= e($current) ?>" dir="ltr" list="families" required>
      <datalist id="families"><?php foreach (array_unique(array_merge(['abar', 'Vazirmatn'], $families)) as $f): ?><option value="<?= e($f) ?>"><?php endforeach; ?></datalist>
    </label>
    <label>فونت جایگزین (اگر فونت اصلی لود نشد)
      <select name="font_fallback">
        <?php foreach (array_unique(array_merge(['Vazirmatn'], $families)) as $f): ?>
          <option value="<?= e($f) ?>" <?= setting('font_fallback', 'Vazirmatn') === $f ? 'selected' : '' ?>><?= e($f) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
  </div>
  <div class="form-actions"><button class="btn btn--primary" type="submit">ذخیره</button></div>
</form>

<div class="card">
  <h2>فایل‌های فونت آپلودشده</h2>
  <?php if (!$fonts): ?><p class="muted">هنوز فونتی آپلود نشده است.</p><?php endif; ?>
  <ul class="member-list">
    <?php foreach ($fonts as $f): ?>
    <li class="member-row">
      <form method="post" class="font-row">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
        <input type="text" name="family" value="<?= e($f['family']) ?>" dir="ltr" title="خانواده">
        <input type="text" name="weight" value="<?= e($f['weight']) ?>" dir="ltr" title="وزن (مثلاً 400 یا 100 900)" style="max-width:110px">
        <select name="style"><option value="normal" <?= $f['style'] === 'normal' ? 'selected' : '' ?>>normal</option><option value="italic" <?= $f['style'] === 'italic' ? 'selected' : '' ?>>italic</option></select>
        <code><?= e($f['file']) ?></code>
        <button class="btn btn--sm btn--ghost" type="submit">ذخیره</button>
      </form>
      <form method="post" class="inline" data-confirm="این فایل فونت حذف شود؟">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
        <button class="btn btn--sm btn--danger" type="submit">حذف</button>
      </form>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php admin_footer(); ?>
