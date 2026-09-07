<?php
declare(strict_types=1);

/** اتصال یکتا به دیتابیس */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $path = $GLOBALS['config']['db_path'];
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    }
    return $pdo;
}

/** اجرای کوئری با پارامتر */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

function scalar(string $sql, array $params = []): mixed
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** خواندن یک تنظیم */
function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (rows('SELECT key, value FROM settings') as $r) {
            $cache[$r['key']] = (string)$r['value'];
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function setting_set(string $key, string $value): void
{
    q('INSERT INTO settings(key, value) VALUES(?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value', [$key, $value]);
}

/** ساخت جداول و داده‌ی اولیه در اولین اجرا */
function db_init(array $config): void
{
    $pdo = db();
    $pdo->exec(<<<SQL
CREATE TABLE IF NOT EXISTS settings (
    key   TEXT PRIMARY KEY,
    value TEXT NOT NULL DEFAULT ''
);
CREATE TABLE IF NOT EXISTS admins (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE TABLE IF NOT EXISTS groups_ (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    kind       TEXT NOT NULL CHECK(kind IN ('orchestra','crew')),
    title      TEXT NOT NULL,
    subtitle   TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS members (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id   INTEGER NOT NULL REFERENCES groups_(id) ON DELETE CASCADE,
    name       TEXT NOT NULL,
    role       TEXT NOT NULL DEFAULT '',
    photo      TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS tracks (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    title      TEXT NOT NULL,
    composer   TEXT NOT NULL DEFAULT '',
    poet       TEXT NOT NULL DEFAULT '',
    arranger   TEXT NOT NULL DEFAULT '',
    singer     TEXT NOT NULL DEFAULT '',
    note       TEXT NOT NULL DEFAULT '',
    lyrics     TEXT NOT NULL DEFAULT '',
    audio      TEXT NOT NULL DEFAULT '',
    video_url  TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS gallery (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    image      TEXT NOT NULL,
    caption    TEXT NOT NULL DEFAULT '',
    sort_order INTEGER NOT NULL DEFAULT 0
);
SQL);

    if ((int)scalar('SELECT COUNT(*) FROM admins') === 0) {
        $a = $config['default_admin'];
        q('INSERT INTO admins(username, password_hash) VALUES(?, ?)', [
            $a['username'],
            password_hash($a['password'], PASSWORD_DEFAULT),
        ]);
    }

    if ((int)scalar('SELECT COUNT(*) FROM settings') === 0) {
        require __DIR__ . '/seed.php';
        seed_database();
    }
}
