<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (auth_user()) {
    redirect(url('admin/index.php'));
}
$notice = apply_emergency_reset();
if (!admins_exist()) {
    redirect(url('admin/setup.php'));
}

/*
 * فرم ورود عمداً توکن CSRF را الزامی نمی‌کند:
 * پیش از ورود هیچ عملیات حساسی وجود ندارد و نباید یک نشست منقضی‌شده (مثلاً تب بازمانده از دیروز
 * یا اولین بازدید بدون کوکی) با خطای «درخواست نامعتبر» مانع ورود شود.
 * پس از ورود موفق، توکن CSRF تازه ساخته می‌شود و همه‌ی فرم‌های پنل با آن محافظت می‌شوند.
 */
$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    if (auth_attempt($username, (string)($_POST['password'] ?? ''))) {
        redirect(url('admin/index.php'));
    }
    $error = 'نام کاربری یا رمز عبور اشتباه است.';
}
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
  <form class="login-card" method="post" action="<?= e(url('admin/login.php')) ?>" autocomplete="on">
    <div class="login-card__logo">♪</div>
    <h1>ورود به پنل مدیریت</h1>
    <p class="muted"><?= e(setting('site_title', 'بروشور کنسرت')) ?></p>
    <?php if ($notice): ?><div class="alert alert--success"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <label>نام کاربری<input type="text" name="username" value="<?= e($username) ?>" required autofocus autocomplete="username" dir="ltr"></label>
    <label>رمز عبور<input type="password" name="password" required autocomplete="current-password" dir="ltr"></label>
    <button class="btn btn--primary btn--block" type="submit">ورود</button>
    <a class="back-link" href="<?= e(url('')) ?>">← بازگشت به سایت</a>
  </form>
</body>
</html>
