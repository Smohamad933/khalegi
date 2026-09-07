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
];
$imageFields = [
    'poster'       => 'پوستر اجرا (تصویر کامل پوستر — در بخش معرفی نمایش داده می‌شود)',
    'logotype'     => 'لوگوتایپ (PNG شفاف — خوشنویسی «دستان»)',
    'theatre_art'  => 'المان خطی تئاتر (پس‌زمینه‌ی سرصفحه — PNG/SVG شفاف)',
    'bird_left'    => 'پرنده‌ی سمت چپ (PNG شفاف)',
    'bird_right'   => 'پرنده‌ی سمت راست (PNG شفاف)',
    'logo'         => 'لوگو / نشان (اختیاری)',
    'hero_desktop' => 'تصویر پس‌زمینه‌ی جایگزین سرصفحه — دسکتاپ (اختیاری)',
    'hero_mobile'  => 'تصویر پس‌زمینه‌ی جایگزین سرصفحه — موبایل (اختیاری)',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($textFields as $key => [$label, $type]) {
        $v = post($key);
        if ($type === 'color' && !preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
            $v = setting($key);
        }
        setting_set($key, $v);
    }
    setting_set('show_tracks', isset($_POST['show_tracks']) ? '1' : '0');
    setting_set('show_gallery', isset($_POST['show_gallery']) ? '1' : '0');

    $errors = [];
    foreach ($imageFields as $key => $label) {
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
    <?php foreach ($textFields as $key => [$label, $type]): ?>
      <label class="<?= $type === 'textarea' ? 'span-2' : '' ?>">
        <?= e($label) ?>
        <?php if ($type === 'textarea'): ?>
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
    <?php foreach ($imageFields as $key => $label): $cur = media_url(setting($key)); ?>
      <div class="upload-box">
        <label><?= e($label) ?><input type="file" name="<?= $key ?>" accept="image/*" data-preview="#pv-<?= $key ?>"></label>
        <img id="pv-<?= $key ?>" class="upload-box__preview" src="<?= e($cur) ?>" alt="" <?= $cur ? '' : 'hidden' ?>>
        <?php if ($cur): ?><label class="check"><input type="checkbox" name="remove_<?= $key ?>" value="1"> حذف تصویر فعلی</label><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <h2>نمایش بخش‌ها</h2>
  <label class="check"><input type="checkbox" name="show_tracks" value="1" <?= setting('show_tracks', '1') === '1' ? 'checked' : '' ?>> نمایش بخش «قطعات موسیقی»</label>
  <label class="check"><input type="checkbox" name="show_gallery" value="1" <?= setting('show_gallery', '1') === '1' ? 'checked' : '' ?>> نمایش بخش «گالری تصاویر»</label>

  <div class="form-actions">
    <button class="btn btn--primary" type="submit">ذخیره‌ی تنظیمات</button>
  </div>
</form>
<?php admin_footer(); ?>
