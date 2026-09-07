<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require_login();

$stats = [
    ['اعضای ارکستر', (int)scalar("SELECT COUNT(*) FROM members m JOIN groups_ g ON g.id = m.group_id WHERE g.kind = 'orchestra'"), 'groups.php?kind=orchestra'],
    ['گروه‌های ساز',  (int)scalar("SELECT COUNT(*) FROM groups_ WHERE kind = 'orchestra'"), 'groups.php?kind=orchestra'],
    ['عوامل اجرایی',  (int)scalar("SELECT COUNT(*) FROM members m JOIN groups_ g ON g.id = m.group_id WHERE g.kind = 'crew'"), 'groups.php?kind=crew'],
    ['قطعات',         (int)scalar("SELECT COUNT(*) FROM tracks"), 'tracks.php'],
    ['تصاویر گالری',  (int)scalar("SELECT COUNT(*) FROM gallery"), 'gallery.php'],
];
$uploadsWritable = is_writable($config['uploads_dir']) || (!is_dir($config['uploads_dir']) && is_writable(dirname($config['uploads_dir'])));
$dataWritable    = is_writable(dirname($config['db_path']));
$isDefaultPass   = password_verify($config['default_admin']['password'], (string)scalar('SELECT password_hash FROM admins WHERE username = ?', [$config['default_admin']['username']]));

admin_header('پیشخوان', 'dashboard');
?>
<?php if ($isDefaultPass): ?>
  <div class="alert alert--warn">رمز عبور مدیر هنوز مقدار پیش‌فرض است. برای امنیت سایت آن را از بخش <a href="account.php">حساب کاربری</a> تغییر دهید.</div>
<?php endif; ?>
<?php if (!$uploadsWritable || !$dataWritable): ?>
  <div class="alert alert--error">پوشه‌ی <code>uploads</code> یا <code>data</code> قابل نوشتن نیست؛ آپلود فایل و ذخیره‌ی اطلاعات کار نخواهد کرد. دسترسی (chmod 775) را بررسی کنید.</div>
<?php endif; ?>

<div class="stats">
  <?php foreach ($stats as [$label, $n, $href]): ?>
    <a class="stat" href="<?= e($href) ?>"><b><?= fa_num($n) ?></b><span><?= e($label) ?></span></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <h2>راهنمای سریع</h2>
  <ul class="help">
    <li><b>اطلاعات کنسرت:</b> عنوان، تاریخ، مکان، پوستر (دسکتاپ و موبایل)، لوگو، رنگ‌ها و متن معرفی.</li>
    <li><b>اعضای ارکستر / عوامل اجرایی:</b> ابتدا یک «گروه» (مثلاً «تار» یا «طراح نور») بسازید، سپس اسامی را به آن اضافه کنید. ترتیب گروه‌ها و اسامی را با کشیدن و رها کردن تغییر دهید.</li>
    <li><b>قطعات:</b> نام قطعه، آهنگساز، شاعر، متن و فایل صوتی (mp3) برای هر قطعه.</li>
    <li><b>گالری:</b> تصاویر اجرا که به‌صورت نوار متحرک در سایت نمایش داده می‌شوند.</li>
  </ul>
</div>
<?php admin_footer(); ?>
