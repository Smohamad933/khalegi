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

    // کاربر اولیه‌ی پنل مدیریت (فقط در اولین اجرا ساخته می‌شود).
    // رمز عبور به‌صورت هش bcrypt نگهداری می‌شود و هیچ‌جا نمایش داده نمی‌شود.
    // برای ساخت هش جدید:  php -r "echo password_hash('رمز-جدید', PASSWORD_BCRYPT);"
    // (پس از ورود می‌توانید نام کاربری و رمز را از پنل → «حساب کاربری» تغییر دهید)
    'default_admin' => [
        'username'      => 'Mohusyn',
        'password_hash' => '$2y$10$YzJewFGc/B7FMkE0OjqzZuVr5b2x.uf0zC329bYc26WzRovKRWwHa',
    ],

    // حداکثر حجم آپلود (مگابایت)
    'max_upload_mb' => 25,

    // مدت اعتبار نشست پنل مدیریت (روز)
    'session_days'  => 14,

    // تشخیص HTTPS: null = خودکار (با پشتیبانی از پروکسی/کلادفلر)، true/false = اجباری
    'force_https'   => null,

    // نام کوکی نشست پنل مدیریت
    'session_name'  => 'khalegi_admin',
];
