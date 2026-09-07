<?php
/** @var array $orchestra @var array $crew @var array $membersByGroup @var array $tracks @var array $gallery */
$siteTitle   = setting('site_title', 'بروشور کنسرت');
$title       = setting('concert_title');
$subtitle    = setting('concert_subtitle');
$tagline     = setting('concert_tagline');
$heroDesktop = media_url(setting('hero_desktop'));
$heroMobile  = media_url(setting('hero_mobile')) ?: $heroDesktop;
$logo        = media_url(setting('logo'));
$ticket      = setting('ticket_url');
$about       = lines(setting('about'));
$hasTracks   = !empty($tracks);
$hasGallery  = !empty($gallery);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?: $siteTitle) ?> | <?= e($tagline ?: 'بروشور الکترونیک') ?></title>
  <meta name="description" content="<?= e($title . ' — ' . $subtitle . ' — ' . setting('venue') . ' — ' . setting('event_date')) ?>">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($subtitle . ' | ' . setting('venue') . ' | ' . setting('event_date')) ?>">
  <?php if ($heroDesktop): ?><meta property="og:image" content="<?= e($heroDesktop) ?>"><?php endif; ?>
  <meta name="theme-color" content="<?= e(setting('color_bg', '#0b1430')) ?>">
  <link rel="icon" href="<?= e($logo ?: url('assets/img/favicon.svg')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
  <style>:root{--bg:<?= e(setting('color_bg', '#0b1430')) ?>;--gold:<?= e(setting('color_gold', '#d9b56a')) ?>;}</style>
</head>
<body>
<?php require __DIR__ . '/ornaments.php'; ?>

<a class="skip-link" href="#main">رفتن به محتوا</a>

<!-- ================= پوستر / سرصفحه ================= -->
<header class="hero" id="top">
  <?php if ($heroDesktop): ?>
  <picture class="hero__media">
    <source media="(max-width: 767px)" srcset="<?= e($heroMobile) ?>">
    <img src="<?= e($heroDesktop) ?>" alt="<?= e($title) ?>" fetchpriority="high">
  </picture>
  <?php endif; ?>
  <div class="hero__shade"></div>

  <div class="hero__content container">
    <?php if ($logo): ?><img class="hero__logo" src="<?= e($logo) ?>" alt=""><?php endif; ?>
    <?php if ($tagline): ?><p class="hero__tagline reveal"><?= e($tagline) ?></p><?php endif; ?>
    <h1 class="hero__title reveal"><?= e($title) ?></h1>
    <?php if ($subtitle): ?><p class="hero__subtitle reveal"><?= e($subtitle) ?></p><?php endif; ?>

    <div class="hero__people reveal">
      <?php if (setting('conductor')): ?><span><small>رهبر ارکستر</small><b><?= e(setting('conductor')) ?></b></span><?php endif; ?>
      <?php if (setting('singer')): ?><span><small>خواننده</small><b><?= e(setting('singer')) ?></b></span><?php endif; ?>
    </div>

    <ul class="hero__meta reveal">
      <?php if (setting('event_date')): ?><li><svg><use href="#icon-calendar"/></svg><?= e(setting('event_date')) ?></li><?php endif; ?>
      <?php if (setting('event_time')): ?><li><svg><use href="#icon-clock"/></svg><?= e(setting('event_time')) ?></li><?php endif; ?>
      <?php if (setting('venue')): ?><li><svg><use href="#icon-pin"/></svg><?= e(setting('venue')) ?></li><?php endif; ?>
    </ul>

    <?php if ($ticket): ?>
    <a class="btn btn--gold reveal" href="<?= e($ticket) ?>" target="_blank" rel="noopener"><svg><use href="#icon-ticket"/></svg> تهیه بلیت</a>
    <?php endif; ?>
  </div>

  <a href="#nav" class="hero__scroll" aria-label="ادامه"><svg><use href="#icon-chevron"/></svg></a>
</header>

<!-- ================= منوی بخش‌ها ================= -->
<nav class="nav" id="nav" aria-label="بخش‌های بروشور">
  <div class="container nav__inner">
    <?php if ($hasTracks): ?><a href="#tracks"><span>قطعــات</span><span>موسیــقی</span></a><?php endif; ?>
    <a href="#orchestra"><span>اعضــای</span><span>ارکـســتر</span></a>
    <a href="#crew"><span>عــوامل</span><span>اجــرایــی</span></a>
    <?php if ($hasGallery): ?><a href="#gallery"><span>گــالری</span><span>تصــاویــر</span></a><?php endif; ?>
  </div>
</nav>

<main id="main">

  <?php if ($about): ?>
  <!-- ================= درباره‌ی اجرا ================= -->
  <section class="section section--about" id="about">
    <div class="container narrow">
      <div class="frame reveal">
        <svg class="frame__c frame__c--tl"><use href="#orn-corner"/></svg>
        <svg class="frame__c frame__c--tr"><use href="#orn-corner"/></svg>
        <svg class="frame__c frame__c--bl"><use href="#orn-corner"/></svg>
        <svg class="frame__c frame__c--br"><use href="#orn-corner"/></svg>
        <?php foreach ($about as $p): ?><p><?= e($p) ?></p><?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($hasTracks): ?>
  <!-- ================= قطعات موسیقی ================= -->
  <section class="section" id="tracks">
    <div class="container">
      <h2 class="section-title reveal">
        <svg class="section-title__wing"><use href="#orn-wing"/></svg>
        <span>قطعات موسیقی</span>
        <svg class="section-title__wing section-title__wing--flip"><use href="#orn-wing"/></svg>
      </h2>

      <div class="tracks">
        <?php foreach ($tracks as $i => $t):
          $audio = media_url($t['audio']);
          $lyr   = lines($t['lyrics']);
          $meta  = array_filter([
              'آهنگساز' => $t['composer'], 'شعر' => $t['poet'], 'تنظیم' => $t['arranger'], 'خواننده' => $t['singer'],
          ]);
        ?>
        <article class="track reveal" id="track-<?= (int)$t['id'] ?>" <?= $audio ? 'data-audio="' . e($audio) . '"' : '' ?>>
          <header class="track__head">
            <span class="track__num"><?= fa_num($i + 1) ?></span>
            <div class="track__info">
              <h3 class="track__title"><?= e($t['title']) ?></h3>
              <?php if ($meta || $t['note']): ?>
              <p class="track__meta">
                <?php foreach ($meta as $k => $v): ?><span><small><?= $k ?></small> <?= e($v) ?></span><?php endforeach; ?>
                <?php if ($t['note']): ?><span class="track__note"><?= e($t['note']) ?></span><?php endif; ?>
              </p>
              <?php endif; ?>
            </div>
            <div class="track__actions">
              <?php if ($audio): ?>
              <button class="track__play" type="button" aria-label="پخش <?= e($t['title']) ?>" aria-pressed="false">
                <svg class="i-play"><use href="#icon-play"/></svg><svg class="i-pause"><use href="#icon-pause"/></svg>
              </button>
              <?php endif; ?>
              <?php if ($t['video_url']): ?>
              <a class="track__video" href="<?= e($t['video_url']) ?>" target="_blank" rel="noopener" aria-label="ویدیو"><svg><use href="#icon-video"/></svg></a>
              <?php endif; ?>
              <?php if ($lyr): ?>
              <button class="track__toggle" type="button" aria-expanded="false" aria-controls="lyrics-<?= (int)$t['id'] ?>" aria-label="متن قطعه"><svg><use href="#icon-chevron"/></svg></button>
              <?php endif; ?>
            </div>
          </header>

          <?php if ($audio): ?>
          <div class="player">
            <span class="player__time player__time--cur">۰۰:۰۰</span>
            <input class="player__seek" type="range" min="0" max="1000" value="0" step="1" aria-label="نوار پخش">
            <span class="player__time player__time--dur">۰۰:۰۰</span>
            <a class="player__dl" href="<?= e($audio) ?>" download aria-label="دانلود"><svg><use href="#icon-download"/></svg></a>
          </div>
          <?php endif; ?>

          <?php if ($lyr): ?>
          <div class="track__lyrics" id="lyrics-<?= (int)$t['id'] ?>" hidden>
            <div class="track__lyrics-inner">
              <h4>متن قطعه</h4>
              <?php foreach ($lyr as $l): ?><p><?= e($l) ?></p><?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ================= اعضای ارکستر ================= -->
  <section class="section section--alt" id="orchestra">
    <div class="container">
      <h2 class="section-title reveal">
        <svg class="section-title__wing"><use href="#orn-wing"/></svg>
        <span>اعضای ارکستر</span>
        <svg class="section-title__wing section-title__wing--flip"><use href="#orn-wing"/></svg>
      </h2>

      <div class="groups">
        <?php foreach ($orchestra as $g): $ms = $membersByGroup[(int)$g['id']] ?? []; ?>
        <div class="group reveal <?= count($ms) > 6 ? 'group--wide' : '' ?>">
          <h3 class="group__title"><?= e($g['title']) ?></h3>
          <?php if ($g['subtitle']): ?><p class="group__subtitle"><?= e($g['subtitle']) ?></p><?php endif; ?>
          <ul class="group__list">
            <?php foreach ($ms as $m): $photo = media_url($m['photo']); ?>
            <li class="member <?= $photo ? 'member--photo' : '' ?>">
              <?php if ($photo): ?><img class="member__photo" src="<?= e($photo) ?>" alt="" loading="lazy"><?php endif; ?>
              <span class="member__name"><?= e($m['name']) ?></span>
              <?php if ($m['role']): ?><small class="member__role"><?= e($m['role']) ?></small><?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <svg class="group__divider"><use href="#orn-divider"/></svg>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ================= عوامل اجرایی ================= -->
  <section class="section" id="crew">
    <div class="container">
      <h2 class="section-title reveal">
        <svg class="section-title__wing"><use href="#orn-wing"/></svg>
        <span>عوامل اجرایی</span>
        <svg class="section-title__wing section-title__wing--flip"><use href="#orn-wing"/></svg>
      </h2>

      <div class="crew">
        <?php foreach ($crew as $g): $ms = $membersByGroup[(int)$g['id']] ?? []; ?>
        <div class="crew__row reveal">
          <h3 class="crew__role"><?= e($g['title']) ?><?php if ($g['subtitle']): ?><small><?= e($g['subtitle']) ?></small><?php endif; ?></h3>
          <div class="crew__names">
            <?php foreach ($ms as $m): ?><span><?= e($m['name']) ?><?php if ($m['role']): ?> <small>(<?= e($m['role']) ?>)</small><?php endif; ?></span><?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if ($hasGallery): ?>
  <!-- ================= گالری تصاویر ================= -->
  <section class="section section--alt" id="gallery">
    <div class="container">
      <h2 class="section-title reveal">
        <svg class="section-title__wing"><use href="#orn-wing"/></svg>
        <span>گالری تصاویر</span>
        <svg class="section-title__wing section-title__wing--flip"><use href="#orn-wing"/></svg>
      </h2>
    </div>
    <div class="marquee" data-marquee>
      <div class="marquee__track">
        <?php foreach ($gallery as $g): $src = media_url($g['image']); ?>
        <a class="marquee__item" href="<?= e($src) ?>" data-lightbox data-caption="<?= e($g['caption']) ?>">
          <img src="<?= e($src) ?>" alt="<?= e($g['caption']) ?>" loading="lazy">
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

</main>

<!-- ================= پاصفحه ================= -->
<footer class="footer">
  <div class="container">
    <svg class="footer__divider"><use href="#orn-divider"/></svg>
    <?php if ($logo): ?><img class="footer__logo" src="<?= e($logo) ?>" alt=""><?php endif; ?>
    <p class="footer__title"><?= e($siteTitle) ?></p>
    <div class="footer__social">
      <?php if (setting('instagram')): ?><a href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener" aria-label="اینستاگرام"><svg><use href="#icon-instagram"/></svg></a><?php endif; ?>
      <?php if (setting('telegram')): ?><a href="<?= e(setting('telegram')) ?>" target="_blank" rel="noopener" aria-label="تلگرام"><svg><use href="#icon-telegram"/></svg></a><?php endif; ?>
      <?php if (setting('website')): ?><a href="<?= e(setting('website')) ?>" target="_blank" rel="noopener" aria-label="وب‌سایت"><svg><use href="#icon-globe"/></svg></a><?php endif; ?>
    </div>
    <?php if (setting('footer_note')): ?><p class="footer__note"><?= e(setting('footer_note')) ?></p><?php endif; ?>
    <a class="footer__admin" href="<?= e(url('admin/')) ?>">ورود مدیریت</a>
  </div>
</footer>

<!-- لایت‌باکس گالری -->
<div class="lightbox" id="lightbox" hidden>
  <button class="lightbox__close" type="button" aria-label="بستن"><svg><use href="#icon-close"/></svg></button>
  <button class="lightbox__nav lightbox__nav--prev" type="button" aria-label="قبلی"><svg><use href="#icon-arrow"/></svg></button>
  <figure>
    <img src="" alt="">
    <figcaption></figcaption>
  </figure>
  <button class="lightbox__nav lightbox__nav--next" type="button" aria-label="بعدی"><svg><use href="#icon-arrow"/></svg></button>
</div>

<a class="to-top" href="#top" aria-label="بازگشت به بالا"><svg><use href="#icon-chevron"/></svg></a>

<script src="<?= e(asset('assets/js/site.js')) ?>" defer></script>
</body>
</html>
