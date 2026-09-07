<?php
/**
 * قالب مشترک پنل مدیریت
 * استفاده: admin_header('عنوان'); ... admin_footer();
 */
declare(strict_types=1);

function admin_header(string $pageTitle, string $active = ''): void
{
    $user  = auth_user();
    $flash = flash_get();
    $nav = [
        'dashboard' => ['index.php',     'پیشخوان'],
        'settings'  => ['settings.php',  'اطلاعات کنسرت'],
        'orchestra' => ['groups.php?kind=orchestra', 'اعضای ارکستر'],
        'crew'      => ['groups.php?kind=crew',      'عوامل اجرایی'],
        'tracks'    => ['tracks.php',    'قطعات'],
        'gallery'   => ['gallery.php',   'گالری'],
        'fonts'     => ['fonts.php',     'فونت سایت'],
        'account'   => ['account.php',   'حساب کاربری'],
    ];
    ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($pageTitle) ?> | پنل مدیریت</title>
  <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('fonts.css.php')) ?>?v=<?= (int)scalar('SELECT COUNT(*) FROM fonts') ?>">
</head>
<body class="admin">
<header class="topbar">
  <div class="topbar__brand">
    <span class="topbar__logo">♪</span>
    <div>
      <b><?= e(setting('site_title', 'بروشور کنسرت')) ?></b>
      <small>پنل مدیریت محتوا</small>
    </div>
  </div>
  <div class="topbar__actions">
    <a class="btn btn--ghost" href="<?= e(url('')) ?>" target="_blank">مشاهده‌ی سایت ↗</a>
    <?php if ($user): ?>
      <span class="topbar__user"><?= e($user['username']) ?></span>
      <form method="post" action="<?= e(url('admin/logout.php')) ?>" class="inline"><?= csrf_field() ?><button class="btn btn--ghost" type="submit">خروج</button></form>
    <?php endif; ?>
  </div>
</header>
<div class="shell">
  <?php if ($user): ?>
  <aside class="sidebar">
    <nav>
      <?php foreach ($nav as $key => [$href, $label]): ?>
        <a href="<?= e(url('admin/' . $href)) ?>" class="<?= $active === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <?php endif; ?>
  <main class="content">
    <h1 class="page-title"><?= e($pageTitle) ?></h1>
    <?php if ($flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
<script src="<?= e(asset('assets/vendor/Sortable.min.js')) ?>"></script>
<script src="<?= e(asset('assets/js/admin.js')) ?>" defer></script>
</body>
</html>
<?php
}
