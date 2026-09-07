<?php
/**
 * مدیریت قطعات موسیقی
 */
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = post('action');

    if ($action === 'reorder') {
        header('Content-Type: application/json; charset=utf-8');
        $ids = array_values(array_filter(array_map('intval', explode(',', post('ids')))));
        db()->beginTransaction();
        foreach ($ids as $i => $id) {
            q('UPDATE tracks SET sort_order = ? WHERE id = ?', [$i + 1, $id]);
        }
        db()->commit();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'delete') {
        $t = row('SELECT * FROM tracks WHERE id = ?', [post_int('id')]);
        if ($t) {
            delete_upload($t['audio']);
            q('DELETE FROM tracks WHERE id = ?', [(int)$t['id']]);
            flash('success', 'قطعه‌ی «' . $t['title'] . '» حذف شد.');
        }
        redirect('tracks.php');
    }

    if ($action === 'save') {
        $id    = post_int('id');
        $title = post('title');
        if ($title === '') {
            flash('error', 'نام قطعه الزامی است.');
            redirect('tracks.php' . ($id ? '?edit=' . $id : ''));
        }
        $existing = $id ? row('SELECT * FROM tracks WHERE id = ?', [$id]) : null;
        $audio = $existing['audio'] ?? '';
        if (!empty($_POST['remove_audio'])) {
            delete_upload($audio);
            $audio = '';
        }
        $err = null;
        $new = handle_upload('audio', 'audio', $err);
        if ($err) {
            flash('error', $err);
            redirect('tracks.php' . ($id ? '?edit=' . $id : ''));
        }
        if ($new) {
            delete_upload($audio);
            $audio = $new;
        }
        $audioUrl = post('audio_url');
        if ($audioUrl !== '' && preg_match('~^https?://~i', $audioUrl)) {
            $audio = $audioUrl;
        }
        $data = [$title, post('composer'), post('poet'), post('arranger'), post('singer'), post('note'), post('lyrics'), $audio, post('video_url')];
        if ($existing) {
            q('UPDATE tracks SET title=?, composer=?, poet=?, arranger=?, singer=?, note=?, lyrics=?, audio=?, video_url=? WHERE id=?', [...$data, $id]);
            flash('success', 'قطعه ویرایش شد.');
        } else {
            $max = (int)scalar('SELECT COALESCE(MAX(sort_order),0) FROM tracks');
            q('INSERT INTO tracks(title, composer, poet, arranger, singer, note, lyrics, audio, video_url, sort_order) VALUES(?,?,?,?,?,?,?,?,?,?)', [...$data, $max + 1]);
            flash('success', 'قطعه‌ی جدید اضافه شد.');
        }
        redirect('tracks.php');
    }
}

$tracks = rows('SELECT * FROM tracks ORDER BY sort_order, id');
$edit   = isset($_GET['edit']) ? row('SELECT * FROM tracks WHERE id = ?', [(int)$_GET['edit']]) : null;
$isNew  = isset($_GET['new']);

admin_header('قطعات موسیقی', 'tracks');
?>

<?php if ($edit || $isNew): $t = $edit ?: ['id' => 0, 'title' => '', 'composer' => '', 'poet' => '', 'arranger' => '', 'singer' => '', 'note' => '', 'lyrics' => '', 'audio' => '', 'video_url' => '']; ?>
<form method="post" enctype="multipart/form-data" class="card form form--highlight" id="top">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
  <h2><?= $edit ? 'ویرایش قطعه' : 'قطعه‌ی جدید' ?></h2>
  <div class="grid-3">
    <label>نام قطعه *<input type="text" name="title" value="<?= e($t['title']) ?>" required></label>
    <label>آهنگساز<input type="text" name="composer" value="<?= e($t['composer']) ?>"></label>
    <label>شاعر<input type="text" name="poet" value="<?= e($t['poet']) ?>"></label>
    <label>تنظیم<input type="text" name="arranger" value="<?= e($t['arranger']) ?>"></label>
    <label>خواننده<input type="text" name="singer" value="<?= e($t['singer']) ?>"></label>
    <label>توضیح کوتاه (مثلاً دستگاه / بی‌کلام)<input type="text" name="note" value="<?= e($t['note']) ?>"></label>
    <label class="span-3">متن قطعه (هر مصرع در یک خط)<textarea name="lyrics" rows="8"><?= e($t['lyrics']) ?></textarea></label>
    <div class="upload-box span-2">
      <label>فایل صوتی (mp3 / m4a / ogg — حداکثر <?= fa_num($config['max_upload_mb']) ?> مگابایت)<input type="file" name="audio" accept="audio/*,video/mp4"></label>
      <?php if ($t['audio']): ?>
        <audio controls src="<?= e(media_url($t['audio'])) ?>" class="audio-preview"></audio>
        <label class="check"><input type="checkbox" name="remove_audio" value="1"> حذف فایل صوتی فعلی</label>
      <?php endif; ?>
      <label>یا آدرس اینترنتی فایل صوتی<input type="url" name="audio_url" dir="ltr" placeholder="https://..." value="<?= preg_match('~^https?://~', $t['audio']) ? e($t['audio']) : '' ?>"></label>
    </div>
    <label>لینک ویدیو (اختیاری)<input type="url" name="video_url" dir="ltr" placeholder="https://" value="<?= e($t['video_url']) ?>"></label>
  </div>
  <div class="form-actions">
    <button class="btn btn--primary" type="submit">ذخیره</button>
    <a class="btn btn--ghost" href="tracks.php">انصراف</a>
  </div>
</form>
<?php else: ?>
<div class="toolbar"><a class="btn btn--primary" href="tracks.php?new=1">＋ قطعه‌ی جدید</a></div>
<?php endif; ?>

<p class="muted hint-drag">ترتیب قطعات را با کشیدن دستگیره ⠿ تغییر دهید.</p>
<div class="card">
  <ul class="track-list" data-sortable="tracks">
    <?php foreach ($tracks as $i => $t): ?>
    <li class="member-row" data-id="<?= (int)$t['id'] ?>">
      <span class="drag-handle">⠿</span>
      <span class="num"><?= fa_num($i + 1) ?></span>
      <span class="member-row__name">
        <b><?= e($t['title']) ?></b>
        <small><?= e(implode(' · ', array_filter([$t['composer'] ? 'آهنگساز: ' . $t['composer'] : '', $t['poet'] ? 'شعر: ' . $t['poet'] : '', $t['note']]))) ?></small>
      </span>
      <span class="tags">
        <?php if ($t['audio']): ?><span class="badge badge--ok">صوت</span><?php endif; ?>
        <?php if ($t['lyrics']): ?><span class="badge">متن</span><?php endif; ?>
        <?php if ($t['video_url']): ?><span class="badge">ویدیو</span><?php endif; ?>
      </span>
      <span class="member-row__actions">
        <a class="btn btn--sm btn--ghost" href="tracks.php?edit=<?= (int)$t['id'] ?>#top">ویرایش</a>
        <form method="post" class="inline" data-confirm="قطعه‌ی «<?= e($t['title']) ?>» حذف شود؟">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <button class="btn btn--sm btn--danger" type="submit">حذف</button>
        </form>
      </span>
    </li>
    <?php endforeach; ?>
    <?php if (!$tracks): ?><li class="member-row member-row--empty">هنوز قطعه‌ای ثبت نشده است.</li><?php endif; ?>
  </ul>
</div>
<?php admin_footer(); ?>
