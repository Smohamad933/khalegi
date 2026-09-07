<?php
/**
 * مدیریت گروه‌ها و اعضا (اعضای ارکستر / عوامل اجرایی)
 */
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require_login();

$kind  = ($_GET['kind'] ?? $_POST['kind'] ?? 'orchestra') === 'crew' ? 'crew' : 'orchestra';
$label = $kind === 'crew' ? 'عوامل اجرایی' : 'اعضای ارکستر';
$groupWord  = $kind === 'crew' ? 'سمت' : 'گروه ساز';
$self  = 'groups.php?kind=' . $kind;

/* ---------------- اکشن‌های POST ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    switch ($action) {
        case 'add_group':
            $title = post('title');
            if ($title === '') {
                flash('error', 'عنوان گروه نمی‌تواند خالی باشد.');
                break;
            }
            $max = (int)scalar('SELECT COALESCE(MAX(sort_order),0) FROM groups_ WHERE kind = ?', [$kind]);
            q('INSERT INTO groups_(kind, title, subtitle, sort_order) VALUES(?,?,?,?)', [$kind, $title, post('subtitle'), $max + 1]);
            $gid = (int)db()->lastInsertId();
            // امکان افزودن چند اسم هم‌زمان (هر خط یک نفر)
            $j = 0;
            foreach (lines(post('names')) as $n) {
                q('INSERT INTO members(group_id, name, sort_order) VALUES(?,?,?)', [$gid, $n, ++$j]);
            }
            flash('success', 'گروه «' . $title . '» اضافه شد.');
            break;

        case 'edit_group':
            $id = post_int('id');
            $title = post('title');
            if ($title === '') {
                flash('error', 'عنوان گروه نمی‌تواند خالی باشد.');
                break;
            }
            q('UPDATE groups_ SET title = ?, subtitle = ? WHERE id = ? AND kind = ?', [$title, post('subtitle'), $id, $kind]);
            flash('success', 'گروه ویرایش شد.');
            break;

        case 'delete_group':
            $id = post_int('id');
            foreach (rows('SELECT photo FROM members WHERE group_id = ?', [$id]) as $m) {
                delete_upload($m['photo']);
            }
            q('DELETE FROM groups_ WHERE id = ? AND kind = ?', [$id, $kind]);
            flash('success', 'گروه و اعضای آن حذف شدند.');
            break;

        case 'add_member':
            $gid = post_int('group_id');
            $names = lines(post('name'));
            if (!$names || !row('SELECT id FROM groups_ WHERE id = ? AND kind = ?', [$gid, $kind])) {
                flash('error', 'نام عضو را وارد کنید.');
                break;
            }
            $err = null;
            $photo = handle_upload('photo', 'image', $err);
            if ($err) {
                flash('error', $err);
                break;
            }
            $max = (int)scalar('SELECT COALESCE(MAX(sort_order),0) FROM members WHERE group_id = ?', [$gid]);
            foreach ($names as $k => $n) {
                q('INSERT INTO members(group_id, name, role, photo, sort_order) VALUES(?,?,?,?,?)', [$gid, $n, post('role'), $k === 0 ? (string)$photo : '', ++$max]);
            }
            flash('success', count($names) > 1 ? count($names) . ' نفر اضافه شدند.' : 'عضو جدید اضافه شد.');
            break;

        case 'edit_member':
            $id = post_int('id');
            $m  = row('SELECT m.* FROM members m JOIN groups_ g ON g.id = m.group_id WHERE m.id = ? AND g.kind = ?', [$id, $kind]);
            if (!$m || post('name') === '') {
                flash('error', 'اطلاعات نامعتبر است.');
                break;
            }
            $photo = $m['photo'];
            if (!empty($_POST['remove_photo'])) {
                delete_upload($photo);
                $photo = '';
            }
            $err = null;
            $new = handle_upload('photo', 'image', $err);
            if ($err) {
                flash('error', $err);
                break;
            }
            if ($new) {
                delete_upload($photo);
                $photo = $new;
            }
            $newGroup = post_int('group_id', (int)$m['group_id']);
            if (!row('SELECT id FROM groups_ WHERE id = ? AND kind = ?', [$newGroup, $kind])) {
                $newGroup = (int)$m['group_id'];
            }
            q('UPDATE members SET name = ?, role = ?, photo = ?, group_id = ? WHERE id = ?', [post('name'), post('role'), $photo, $newGroup, $id]);
            flash('success', 'عضو ویرایش شد.');
            break;

        case 'delete_member':
            $id = post_int('id');
            $m  = row('SELECT m.* FROM members m JOIN groups_ g ON g.id = m.group_id WHERE m.id = ? AND g.kind = ?', [$id, $kind]);
            if ($m) {
                delete_upload($m['photo']);
                q('DELETE FROM members WHERE id = ?', [$id]);
                flash('success', '«' . $m['name'] . '» حذف شد.');
            }
            break;

        case 'reorder':
            // درخواست AJAX: ترتیب گروه‌ها یا اعضا
            header('Content-Type: application/json; charset=utf-8');
            $type = post('type');
            $ids  = array_values(array_filter(array_map('intval', explode(',', post('ids')))));
            $table = $type === 'groups' ? 'groups_' : 'members';
            db()->beginTransaction();
            foreach ($ids as $i => $id) {
                q("UPDATE $table SET sort_order = ? WHERE id = ?", [$i + 1, $id]);
            }
            db()->commit();
            echo json_encode(['ok' => true]);
            exit;
    }
    redirect($self);
}

/* ---------------- نمایش ---------------- */
$groups  = rows('SELECT * FROM groups_ WHERE kind = ? ORDER BY sort_order, id', [$kind]);
$members = [];
foreach (rows('SELECT m.* FROM members m JOIN groups_ g ON g.id = m.group_id WHERE g.kind = ? ORDER BY m.sort_order, m.id', [$kind]) as $m) {
    $members[(int)$m['group_id']][] = $m;
}
$editMember = isset($_GET['edit']) ? row('SELECT m.* FROM members m JOIN groups_ g ON g.id = m.group_id WHERE m.id = ? AND g.kind = ?', [(int)$_GET['edit'], $kind]) : null;
$editGroup  = isset($_GET['edit_group']) ? row('SELECT * FROM groups_ WHERE id = ? AND kind = ?', [(int)$_GET['edit_group'], $kind]) : null;

admin_header($label, $kind);
?>

<?php if ($editMember): ?>
<form method="post" enctype="multipart/form-data" class="card form form--highlight">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="edit_member">
  <input type="hidden" name="kind" value="<?= $kind ?>">
  <input type="hidden" name="id" value="<?= (int)$editMember['id'] ?>">
  <h2>ویرایش عضو</h2>
  <div class="grid-3">
    <label>نام و نام خانوادگی<input type="text" name="name" value="<?= e($editMember['name']) ?>" required></label>
    <label>توضیح / سمت (اختیاری)<input type="text" name="role" value="<?= e($editMember['role']) ?>" placeholder="مثلاً: سرپرست گروه"></label>
    <label><?= e($groupWord) ?>
      <select name="group_id">
        <?php foreach ($groups as $g): ?><option value="<?= (int)$g['id'] ?>" <?= (int)$g['id'] === (int)$editMember['group_id'] ? 'selected' : '' ?>><?= e($g['title']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <div class="upload-box">
      <label>عکس (اختیاری)<input type="file" name="photo" accept="image/*" data-preview="#pv-member"></label>
      <img id="pv-member" class="upload-box__preview upload-box__preview--round" src="<?= e(media_url($editMember['photo'])) ?>" alt="" <?= $editMember['photo'] ? '' : 'hidden' ?>>
      <?php if ($editMember['photo']): ?><label class="check"><input type="checkbox" name="remove_photo" value="1"> حذف عکس</label><?php endif; ?>
    </div>
  </div>
  <div class="form-actions">
    <button class="btn btn--primary" type="submit">ذخیره‌ی تغییرات</button>
    <a class="btn btn--ghost" href="<?= e($self) ?>">انصراف</a>
  </div>
</form>
<?php endif; ?>

<?php if ($editGroup): ?>
<form method="post" class="card form form--highlight">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="edit_group">
  <input type="hidden" name="kind" value="<?= $kind ?>">
  <input type="hidden" name="id" value="<?= (int)$editGroup['id'] ?>">
  <h2>ویرایش <?= e($groupWord) ?></h2>
  <div class="grid-2">
    <label>عنوان<input type="text" name="title" value="<?= e($editGroup['title']) ?>" required></label>
    <label>زیرعنوان (اختیاری)<input type="text" name="subtitle" value="<?= e($editGroup['subtitle']) ?>" placeholder="مثلاً: به سرپرستی ..."></label>
  </div>
  <div class="form-actions">
    <button class="btn btn--primary" type="submit">ذخیره‌ی تغییرات</button>
    <a class="btn btn--ghost" href="<?= e($self) ?>">انصراف</a>
  </div>
</form>
<?php endif; ?>

<details class="card form" <?= empty($groups) ? 'open' : '' ?>>
  <summary class="card__summary">＋ افزودن <?= e($groupWord) ?> جدید</summary>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_group">
    <input type="hidden" name="kind" value="<?= $kind ?>">
    <div class="grid-2">
      <label>عنوان <?= e($groupWord) ?> *<input type="text" name="title" required placeholder="<?= $kind === 'crew' ? 'مثلاً: طراح نور' : 'مثلاً: کمانچه' ?>"></label>
      <label>زیرعنوان (اختیاری)<input type="text" name="subtitle" placeholder="مثلاً: به سرپرستی ..."></label>
      <label class="span-2">اسامی اعضا (اختیاری — هر نفر در یک خط)<textarea name="names" rows="3" placeholder="نام نفر اول&#10;نام نفر دوم"></textarea></label>
    </div>
    <div class="form-actions"><button class="btn btn--primary" type="submit">افزودن</button></div>
  </form>
</details>

<p class="muted hint-drag">برای تغییر ترتیب، گروه‌ها (از دستگیره ⠿) و اسامی را بکشید و رها کنید. ترتیب به‌صورت خودکار ذخیره می‌شود.</p>

<div class="group-list" data-sortable="groups" data-kind="<?= $kind ?>">
  <?php foreach ($groups as $g): $ms = $members[(int)$g['id']] ?? []; ?>
  <section class="card group-card" data-id="<?= (int)$g['id'] ?>">
    <header class="group-card__head">
      <span class="drag-handle" title="جابه‌جایی">⠿</span>
      <h2><?= e($g['title']) ?> <?php if ($g['subtitle']): ?><small><?= e($g['subtitle']) ?></small><?php endif; ?> <span class="badge"><?= fa_num(count($ms)) ?> نفر</span></h2>
      <div class="group-card__actions">
        <a class="btn btn--sm btn--ghost" href="<?= e($self) ?>&edit_group=<?= (int)$g['id'] ?>">ویرایش</a>
        <form method="post" class="inline" data-confirm="گروه «<?= e($g['title']) ?>» و همه‌ی اعضای آن حذف شوند؟">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete_group"><input type="hidden" name="kind" value="<?= $kind ?>"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
          <button class="btn btn--sm btn--danger" type="submit">حذف گروه</button>
        </form>
      </div>
    </header>

    <ul class="member-list" data-sortable="members">
      <?php foreach ($ms as $m): ?>
      <li class="member-row" data-id="<?= (int)$m['id'] ?>">
        <span class="drag-handle" title="جابه‌جایی">⠿</span>
        <?php if ($m['photo']): ?><img class="avatar" src="<?= e(media_url($m['photo'])) ?>" alt=""><?php else: ?><span class="avatar avatar--empty"><?= e(mb_substr($m['name'], 0, 1)) ?></span><?php endif; ?>
        <span class="member-row__name"><?= e($m['name']) ?> <?php if ($m['role']): ?><small><?= e($m['role']) ?></small><?php endif; ?></span>
        <span class="member-row__actions">
          <a class="btn btn--sm btn--ghost" href="<?= e($self) ?>&edit=<?= (int)$m['id'] ?>#top">ویرایش</a>
          <form method="post" class="inline" data-confirm="«<?= e($m['name']) ?>» حذف شود؟">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete_member"><input type="hidden" name="kind" value="<?= $kind ?>"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <button class="btn btn--sm btn--danger" type="submit">حذف</button>
          </form>
        </span>
      </li>
      <?php endforeach; ?>
      <?php if (!$ms): ?><li class="member-row member-row--empty">هنوز عضوی ثبت نشده است.</li><?php endif; ?>
    </ul>

    <form method="post" enctype="multipart/form-data" class="add-member">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add_member">
      <input type="hidden" name="kind" value="<?= $kind ?>">
      <input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>">
      <textarea name="name" rows="1" required placeholder="نام عضو جدید (برای چند نفر، هر نام در یک خط)" data-autogrow></textarea>
      <input type="text" name="role" placeholder="توضیح (اختیاری)">
      <label class="file-btn" title="عکس (اختیاری)">📷<input type="file" name="photo" accept="image/*"></label>
      <button class="btn btn--sm btn--primary" type="submit">＋ افزودن</button>
    </form>
  </section>
  <?php endforeach; ?>
</div>

<?php if (!$groups): ?><div class="card muted">هنوز گروهی تعریف نشده است. از فرم بالا شروع کنید.</div><?php endif; ?>

<?php admin_footer(); ?>
