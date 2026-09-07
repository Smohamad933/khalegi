<?php
/**
 * ساخت اولین حساب مدیر — فقط وقتی هیچ مدیری وجود ندارد نمایش داده می‌شود
 * (مثلاً پس از نصب از روی ZIP دانلودی، که هش رمز در آن قرار نمی‌گیرد).
 */
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';

if (admins_exist()) {
    redirect(url('admin/login.php'));
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $pass     = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['password_confirm'] ?? '');
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,32}$/', $username)) {
        $error = 'نام کاربری باید ۳ تا ۳۲ حرف لاتین/عدد باشد.';
    } elseif (mb_strlen($pass) < 8) {
        $error = 'رمز عبور باید حداقل ۸ کاراکتر باشد.';
    } elseif ($pass !== $confirm) {
        $error = 'تکرار رمز عبور مطابقت ندارد.';
    } elseif (admins_exist()) {
        redirect(url('admin/login.php'));
    } else {
        q('INSERT INTO admins(username, password_hash) VALUES(?, ?)', [$username, password_hash($pass, PASSWORD_DEFAULT)]);
        auth_attempt($username, $pass);
        redirect(url('admin/index.php'));
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <title>ساخت حساب مدیر</title>
  <link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('fonts.css.php')) ?>">
</head>
<body class="admin login-page">
  <form class="login-card" method="post" action="<?= e(url('admin/setup.php')) ?>" autocomplete="off">
    <div class="login-card__logo">♪</div>
    <h1>ساخت حساب مدیر</h1>
    <p class="muted">هنوز هیچ حساب مدیری ساخته نشده است. نام کاربری و رمز عبور دلخواه خود را وارد کنید.</p>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <label>نام کاربری<input type="text" name="username" value="<?= e($username) ?>" required autofocus dir="ltr" autocomplete="username"></label>
    <label>رمز عبور (حداقل ۸ کاراکتر)<input type="password" name="password" required dir="ltr" autocomplete="new-password"></label>
    <label>تکرار رمز عبور<input type="password" name="password_confirm" required dir="ltr" autocomplete="new-password"></label>
    <button class="btn btn--primary btn--block" type="submit">ساخت حساب و ورود</button>
    <a class="back-link" href="<?= e(url('')) ?>">← بازگشت به سایت</a>
  </form>
</body>
</html>
