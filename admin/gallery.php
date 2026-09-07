<?php
/**
 * مدیریت گالری تصاویر
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
            q('UPDATE gallery SET sort_order = ? WHERE id = ?', [$i + 1, $id]);
        }
        db()->commit();
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($action === 'upload') {
        // آپلود چندتایی
        $files = $_FILES['images'] ?? null;
        $count = 0;
        $errors = [];
        if ($files && is_array($files['name'])) {
            $max = (int)scalar('SELECT COALESCE(MAX(sort_order),0) FROM gallery');
            foreach ($files['name'] as $i => $n) {
                if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $_FILES['_one'] = [
                    'name' => $n, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i], 'size' => $files['size'][$i],
                ];
                $err = null;
                $saved = handle_upload('_one', 'image', $err);
                if ($saved) {
                    q('INSERT INTO gallery(image, caption, sort_order) VALUES(?,?,?)', [$saved, post('caption'), ++$max]);
                    $count++;
                } elseif ($err) {
                    $errors[] = $n . ': ' . $err;
                }
            }
        }
        flash($errors ? 'error' : 'success', ($count ? fa_num($count) . ' تصویر اضافه شد. ' : '') . implode(' | ', $errors));
        redirect('gallery.php');
    }

    if ($action === 'caption') {
        q('UPDATE gallery SET caption = ? WHERE id = ?', [post('caption'), post_int('id')]);
        flash('success', 'توضیح تصویر ذخیره شد.');
        redirect('gallery.php');
    }

    if ($action === 'delete') {
        $g = row('SELECT * FROM gallery WHERE id = ?', [post_int('id')]);
        if ($g) {
            if (!str_starts_with($g['image'], 'assets/')) {
                delete_upload($g['image']);
            }
            q('DELETE FROM gallery WHERE id = ?', [(int)$g['id']]);
            flash('success', 'تصویر حذف شد.');
        }
        redirect('gallery.php');
    }
}

$items = rows('SELECT * FROM gallery ORDER BY sort_order, id');
admin_header('گالری تصاویر', 'gallery');
?>
<form method="post" enctype="multipart/form-data" class="card form">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="upload">
  <h2>افزودن تصویر</h2>
  <div class="grid-2">
    <label>انتخاب تصاویر (می‌توانید چند فایل را هم‌زمان انتخاب کنید)<input type="file" name="images[]" accept="image/*" multiple required></label>
    <label>توضیح مشترک (اختیاری)<input type="text" name="caption" placeholder="مثلاً: اجرای شب اول"></label>
  </div>
  <div class="form-actions"><button class="btn btn--primary" type="submit">آپلود</button></div>
</form>

<p class="muted hint-drag">ترتیب نمایش را با کشیدن تصاویر تغییر دهید.</p>
<div class="gallery-grid" data-sortable="gallery">
  <?php foreach ($items as $g): ?>
  <div class="gallery-item card" data-id="<?= (int)$g['id'] ?>">
    <img src="<?= e(media_url($g['image'])) ?>" alt="" class="drag-handle">
    <form method="post" class="gallery-item__caption">
      <?= csrf_field() ?><input type="hidden" name="action" value="caption"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
      <input type="text" name="caption" value="<?= e($g['caption']) ?>" placeholder="توضیح تصویر">
      <button class="btn btn--sm btn--ghost" type="submit">ذخیره</button>
    </form>
    <form method="post" class="inline" data-confirm="این تصویر حذف شود؟">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
      <button class="btn btn--sm btn--danger btn--block" type="submit">حذف</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>
<?php if (!$items): ?><div class="card muted">هنوز تصویری اضافه نشده است.</div><?php endif; ?>
<?php admin_footer(); ?>
