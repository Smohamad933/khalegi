<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (auth_user()) {
    redirect(url('admin/index.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (auth_attempt(post('username'), post('password'))) {
        redirect(url('admin/index.php'));
    }
    $error = 'نام کاربری یا رمز عبور اشتباه است.';
}
$isDefault = (bool)row('SELECT 1 FROM admins WHERE username = ?', [$config['default_admin']['username']]);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>ورود به پنل مدیریت</title>
  <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('fonts.css.php')) ?>">
</head>
<body class="admin login-page">
  <form class="login-card" method="post">
    <?= csrf_field() ?>
    <div class="login-card__logo">♪</div>
    <h1>ورود به پنل مدیریت</h1>
    <p class="muted"><?= e(setting('site_title', 'بروشور کنسرت')) ?></p>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <label>نام کاربری<input type="text" name="username" required autofocus autocomplete="username"></label>
    <label>رمز عبور<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn--primary btn--block" type="submit">ورود</button>
    <?php if ($isDefault): ?>
      <p class="hint">کاربر پیش‌فرض: <code>admin</code> / <code>admin123</code> — پس از ورود حتماً آن را از بخش «حساب کاربری» تغییر دهید.</p>
    <?php endif; ?>
    <a class="back-link" href="<?= e(url('')) ?>">← بازگشت به سایت</a>
  </form>
</body>
</html>
