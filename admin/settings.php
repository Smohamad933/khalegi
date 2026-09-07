<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require_login();

$textFields = [
    'site_title'       => ['نام سایت / ارکستر', 'text'],
    'concert_title'    => ['عنوان کنسرت', 'text'],
    'concert_subtitle' => ['زیرعنوان', 'text'],
    'concert_note'     => ['یادداشت زیر عنوان (مثلاً یادواره / بزرگداشت — هر مورد در یک خط)', 'textarea'],
    'concert_tagline'  => ['برچسب بالای عنوان (مثلاً «بروشور الکترونیک اجرا»)', 'text'],
    'conductor'        => ['رهبر ارکستر', 'text'],
    'singer'           => ['خواننده', 'text'],
    'event_date'       => ['تاریخ اجرا', 'text'],
    'event_time'       => ['ساعت اجرا', 'text'],
    'venue'            => ['محل اجرا', 'text'],
    'ticket_url'       => ['لینک خرید بلیت', 'url'],
    'about'            => ['متن معرفی اجرا (هر پاراگراف در یک خط)', 'textarea'],
    'instagram'        => ['لینک اینستاگرام', 'url'],
    'telegram'         => ['لینک تلگرام', 'url'],
    'website'          => ['لینک وب‌سایت', 'url'],
    'footer_note'      => ['متن پاصفحه', 'text'],
    'color_bg'         => ['رنگ زمینه (کرم)', 'color'],
    'color_accent'     => ['رنگ تأکید (نارنجی/قرمز عنوان)', 'color'],
    'color_teal'       => ['رنگ فیروزه‌ای (تذهیب/پرنده‌ها)', 'color'],
    'color_line'       => ['رنگ خطوط (قرمز خطی)', 'color'],
    'color_gold'       => ['رنگ طلایی', 'color'],
    'hero_mode'        => ['چیدمان سرصفحه', 'select', [
        'auto'   => 'خودکار (اگر پوستر آپلود شده باشد، خودِ پوستر؛ وگرنه ترکیب المان‌ها)',
        'poster' => 'تصویر پوستر (مثل سایت نمونه) + پرنده‌ها دو طرف آن',
        'art'    => 'ترکیب المان‌ها (المان تئاتر + عنوان + لوگوتایپ + پرنده‌ها)',
        'photo'  => 'عکس پس‌زمینه‌ی تمام‌صفحه + متن روی آن',
    ]],
];
/*
 * جایگاه‌های تصویر: [برچسب, راهنمای اندازه/فرمت]
 * اندازه‌ها پیشنهادی‌اند؛ تصویر بزرگ‌تر خودکار کوچک می‌شود ولی تصویر خیلی کوچک تار می‌افتد.
 */
$imageFields = [
    'poster'       => ['پوستر اجرا (تصویر کامل پوستر)',                  'عمودی، نسبت ۷:۱۰ (مثلاً ۱۴۰۰×۲۰۰۰ پیکسل) — JPG/PNG/WebP، حداکثر ۲ مگابایت'],
    'logotype'     => ['لوگوتایپ (خوشنویسی «دستان»)',                    'PNG شفاف، عرض ۱۶۰۰–۲۰۰۰ پیکسل (در سایت تا ۴۲۰ پیکسل نمایش داده می‌شود)'],
    'theatre_art'  => ['المان خطی تئاتر (پس‌زمینه‌ی سرصفحه)',              'SVG یا PNG شفاف، افقی، عرض حداقل ۲۲۰۰ پیکسل (نسبت تقریبی ۳:۲)'],
    'bird_left'    => ['پرنده‌ی سمت چپ (رو به راست)',                     'PNG/SVG شفاف، مربع‌گونه، حدود ۶۰۰×۶۰۰ پیکسل'],
    'bird_right'   => ['پرنده‌ی سمت راست (رو به چپ)',                     'PNG/SVG شفاف، مربع‌گونه، حدود ۶۰۰×۶۰۰ پیکسل'],
    'logo'         => ['لوگو / نشان (اختیاری)',                            'PNG/SVG شفاف، مربع، حداقل ۲۵۶×۲۵۶ پیکسل'],
    'hero_desktop' => ['عکس پس‌زمینه‌ی سرصفحه — دسکتاپ (چیدمان «عکس پس‌زمینه»)', 'افقی، ۱۹۲۰×۱۰۸۰ پیکسل — JPG/WebP'],
    'hero_mobile'  => ['نسخه‌ی موبایل پوستر / عکس پس‌زمینه (اختیاری)',    'عمودی، ۱۰۸۰×۱۹۲۰ پیکسل — JPG/WebP'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($textFields as $key => $def) {
        [$label, $type] = $def;
        $v = post($key);
        if ($type === 'color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
            $v = setting($key);
        }
        if ($type === 'select' && !isset($def[2][$v])) {
            $v = (string)array_key_first($def[2]);
        }
        setting_set($key, $v);
    }
    foreach (['show_tracks', 'show_gallery', 'show_ticket', 'show_download', 'show_admin_link'] as $flag) {
        setting_set($flag, isset($_POST[$flag]) ? '1' : '0');
    }

    $errors = [];
    foreach ($imageFields as $key => [$label, $hint]) {
        if (!empty($_POST['remove_' . $key])) {
            if (!str_starts_with(setting($key), 'assets/')) {
                delete_upload(setting($key));
            }
            setting_set($key, '');
            continue;
        }
        $err = null;
        $file = handle_upload($key, 'image', $err);
        if ($err) {
            $errors[] = $label . ': ' . $err;
        } elseif ($file) {
            $old = setting($key);
            if (!str_starts_with($old, 'assets/')) {
                delete_upload($old);
            }
            setting_set($key, $file);
        }
    }
    flash($errors ? 'error' : 'success', $errors ? implode(' | ', $errors) : 'تنظیمات ذخیره شد.');
    redirect('settings.php');
}

admin_header('اطلاعات کنسرت', 'settings');
?>
<form method="post" enctype="multipart/form-data" class="card form">
  <?= csrf_field() ?>
  <div class="grid-2">
    <?php foreach ($textFields as $key => $def): [$label, $type] = $def; ?>
      <label class="<?= in_array($type, ['textarea', 'select'], true) ? 'span-2' : '' ?>">
        <?= e($label) ?>
        <?php if ($type === 'select'): ?>
          <select name="<?= $key ?>">
            <?php foreach ($def[2] as $val => $text): ?><option value="<?= e($val) ?>" <?= setting($key, 'auto') === $val ? 'selected' : '' ?>><?= e($text) ?></option><?php endforeach; ?>
          </select>
        <?php elseif ($type === 'textarea'): ?>
          <textarea name="<?= $key ?>" rows="5"><?= e(setting($key)) ?></textarea>
        <?php elseif ($type === 'color'): ?>
          <span class="color-input"><input type="color" name="<?= $key ?>" value="<?= e(setting($key) ?: '#000000') ?>"><code><?= e(setting($key)) ?></code></span>
        <?php else: ?>
          <input type="<?= $type ?>" name="<?= $key ?>" value="<?= e(setting($key)) ?>" <?= $type === 'url' ? 'dir="ltr" placeholder="https://"' : '' ?>>
        <?php endif; ?>
      </label>
    <?php endforeach; ?>
  </div>

  <h2>تصاویر</h2>
  <div class="grid-3">
    <?php foreach ($imageFields as $key => [$label, $hint]): $cur = media_url(setting($key)); $dim = image_dimensions(setting($key)); ?>
      <div class="upload-box">
        <label><?= e($label) ?>
          <small class="upload-box__hint">اندازه‌ی پیشنهادی: <?= e($hint) ?></small>
          <input type="file" name="<?= $key ?>" accept="image/*" data-preview="#pv-<?= $key ?>">
        </label>
        <img id="pv-<?= $key ?>" class="upload-box__preview" src="<?= e($cur) ?>" alt="" <?= $cur ? '' : 'hidden' ?>>
        <?php if ($cur): ?>
          <small class="muted">فعلی: <?= $dim ? fa_num($dim[0]) . '×' . fa_num($dim[1]) . ' پیکسل' : '' ?> <?= str_starts_with(setting($key), 'assets/') ? '(پیش‌فرض)' : '' ?></small>
          <label class="check"><input type="checkbox" name="remove_<?= $key ?>" value="1"> حذف تصویر فعلی</label>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <h2>نمایش بخش‌ها و دکمه‌ها</h2>
  <label class="check"><input type="checkbox" name="show_tracks" value="1" <?= setting('show_tracks', '1') === '1' ? 'checked' : '' ?>> نمایش بخش «قطعات موسیقی»</label>
  <label class="check"><input type="checkbox" name="show_gallery" value="1" <?= setting('show_gallery', '1') === '1' ? 'checked' : '' ?>> نمایش بخش «گالری تصاویر»</label>
  <label class="check"><input type="checkbox" name="show_ticket" value="1" <?= setting('show_ticket', '1') === '1' ? 'checked' : '' ?>> نمایش دکمه‌ی «تهیه بلیت» (لینک آن در فیلد «لینک خرید بلیت» بالا)</label>
  <label class="check"><input type="checkbox" name="show_download" value="1" <?= setting('show_download', '1') === '1' ? 'checked' : '' ?>> نمایش دکمه‌ی «دانلود سورس سایت (ZIP)» در پاصفحه</label>
  <label class="check"><input type="checkbox" name="show_admin_link" value="1" <?= setting('show_admin_link', '1') === '1' ? 'checked' : '' ?>> نمایش لینک «ورود مدیریت» در پاصفحه</label>
  <p class="muted" style="font-size:.8rem;margin:-.3rem 0 .6rem">اگر لینک را پنهان کنید، همچنان می‌توانید با آدرس <code><?= e(url('admin/')) ?></code> وارد شوید.</p>

  <div class="form-actions">
    <button class="btn btn--primary" type="submit">ذخیره‌ی تنظیمات</button>
  </div>
</form>
<?php admin_footer(); ?>
