<?php
/**
 * صفحه‌ی اصلی بروشور الکترونیک کنسرت
 */
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$groups = rows("SELECT * FROM groups_ ORDER BY sort_order, id");
$membersByGroup = [];
foreach (rows("SELECT * FROM members ORDER BY sort_order, id") as $m) {
    $membersByGroup[(int)$m['group_id']][] = $m;
}
$orchestra = array_values(array_filter($groups, fn($g) => $g['kind'] === 'orchestra'));
$crew      = array_values(array_filter($groups, fn($g) => $g['kind'] === 'crew'));
$tracks    = setting('show_tracks', '1') === '1' ? rows("SELECT * FROM tracks ORDER BY sort_order, id") : [];
$gallery   = setting('show_gallery', '1') === '1' ? rows("SELECT * FROM gallery ORDER BY sort_order, id") : [];

view('public/home', compact('orchestra', 'crew', 'membersByGroup', 'tracks', 'gallery'));
