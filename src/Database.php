<?php
declare(strict_types=1);

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $pdo = new PDO('sqlite:' . DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->exec('PRAGMA foreign_keys = ON');
            $pdo->exec('PRAGMA journal_mode = WAL');
            self::migrate($pdo);
            self::$pdo = $pdo;
        }
        return self::$pdo;
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS users (
                id              INTEGER PRIMARY KEY AUTOINCREMENT,
                username        TEXT NOT NULL UNIQUE COLLATE NOCASE,
                display_name    TEXT NOT NULL,
                email           TEXT NOT NULL UNIQUE COLLATE NOCASE,
                password_hash   TEXT NOT NULL,
                bio             TEXT NOT NULL DEFAULT '',
                accepting       INTEGER NOT NULL DEFAULT 1,
                review_first    INTEGER NOT NULL DEFAULT 0,
                created_at      TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE TABLE IF NOT EXISTS questions (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                body         TEXT NOT NULL,
                answer       TEXT,
                answered_at  TEXT,
                is_hidden    INTEGER NOT NULL DEFAULT 0,
                is_pinned    INTEGER NOT NULL DEFAULT 0,
                upvotes      INTEGER NOT NULL DEFAULT 0,
                asker_hash   TEXT NOT NULL,
                created_at   TEXT NOT NULL DEFAULT (datetime('now'))
            );
            CREATE INDEX IF NOT EXISTS idx_questions_user ON questions(user_id, answered_at);
            CREATE TABLE IF NOT EXISTS upvotes (
                question_id  INTEGER NOT NULL REFERENCES questions(id) ON DELETE CASCADE,
                voter_hash   TEXT NOT NULL,
                PRIMARY KEY (question_id, voter_hash)
            );
        SQL);
    }
}
