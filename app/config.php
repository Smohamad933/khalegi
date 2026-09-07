<?php
/**
 * تنظیمات پایه‌ی برنامه
 * برای استقرار روی هاست کافی است این فایل را (در صورت نیاز) ویرایش کنید.
 */
return [
    // مسیر فایل دیتابیس SQLite (به‌صورت خودکار ساخته می‌شود)
    'db_path'       => dirname(__DIR__) . '/data/brochure.sqlite',

    // پوشه‌ی آپلود فایل‌ها (عکس، صدا) — باید قابل نوشتن باشد
    'uploads_dir'   => dirname(__DIR__) . '/uploads',
    'uploads_url'   => 'uploads',

    // کاربر پیش‌فرض پنل مدیریت (فقط در اولین اجرا ساخته می‌شود)
    'default_admin' => ['username' => 'admin', 'password' => 'admin123'],

    // حداکثر حجم آپلود (مگابایت)
    'max_upload_mb' => 25,

    // نام کوکی نشست پنل مدیریت
    'session_name'  => 'khalegi_admin',
];
