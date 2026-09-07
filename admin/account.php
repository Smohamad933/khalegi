<?php
/**
 * تغییر نام کاربری / رمز عبور مدیر
 */
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = post('current_password');
    $u = row('SELECT * FROM admins WHERE id = ?', [(int)$user['id']]);
    if (!$u || !password_verify($current, $u['password_hash'])) {
        flash('error', 'رمز عبور فعلی اشتباه است.');
        redirect('account.php');
    }
    $username = post('username');
    $new      = post('new_password');
    $confirm  = post('new_password_confirm');
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,32}$/', $username)) {
        flash('error', 'نام کاربری باید ۳ تا ۳۲ حرف لاتین/عدد باشد.');
        redirect('account.php');
    }
    if ($new !== '' && mb_strlen($new) < 8) {
        flash('error', 'رمز عبور جدید باید حداقل ۸ کاراکتر باشد.');
        redirect('account.php');
    }
    if ($new !== $confirm) {
        flash('error', 'تکرار رمز عبور مطابقت ندارد.');
        redirect('account.php');
    }
    if (row('SELECT id FROM admins WHERE username = ? AND id != ?', [$username, (int)$user['id']])) {
        flash('error', 'این نام کاربری قبلاً استفاده شده است.');
        redirect('account.php');
    }
    if ($new !== '') {
        q('UPDATE admins SET username = ?, password_hash = ? WHERE id = ?', [$username, password_hash($new, PASSWORD_DEFAULT), (int)$user['id']]);
    } else {
        q('UPDATE admins SET username = ? WHERE id = ?', [$username, (int)$user['id']]);
    }
    flash('success', 'اطلاعات حساب به‌روزرسانی شد.');
    redirect('account.php');
}

admin_header('حساب کاربری', 'account');
?>
<form method="post" class="card form" style="max-width:560px">
  <?= csrf_field() ?>
  <label>نام کاربری<input type="text" name="username" value="<?= e($user['username']) ?>" dir="ltr" required></label>
  <label>رمز عبور فعلی *<input type="password" name="current_password" required autocomplete="current-password"></label>
  <label>رمز عبور جدید (خالی = بدون تغییر)<input type="password" name="new_password" autocomplete="new-password"></label>
  <label>تکرار رمز عبور جدید<input type="password" name="new_password_confirm" autocomplete="new-password"></label>
  <div class="form-actions"><button class="btn btn--primary" type="submit">ذخیره</button></div>
</form>
<?php admin_footer(); ?>
