<?php
declare(strict_types=1);

final class UserRepository
{
    public function __construct(private PDO $db) {}

    public function find(int $id): ?array
    {
        $s = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $s = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $s->execute([$username]);
        return $s->fetch() ?: null;
    }

    public function findByLogin(string $login): ?array
    {
        $s = $this->db->prepare('SELECT * FROM users WHERE email = ? OR username = ?');
        $s->execute([$login, ltrim($login, '@')]);
        return $s->fetch() ?: null;
    }

    public function exists(string $column, string $value): bool
    {
        if (!in_array($column, ['username', 'email'], true)) {
            throw new InvalidArgumentException('Bad column');
        }
        $s = $this->db->prepare("SELECT 1 FROM users WHERE $column = ?");
        $s->execute([$value]);
        return (bool) $s->fetchColumn();
    }

    public function create(string $username, string $displayName, string $email, string $password): int
    {
        $s = $this->db->prepare(
            'INSERT INTO users (username, display_name, email, password_hash, bio) VALUES (?, ?, ?, ?, ?)'
        );
        $s->execute([$username, $displayName, $email, password_hash($password, PASSWORD_DEFAULT),
                     'Ask me anything — I read every question.']);
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, string $displayName, string $bio, bool $accepting, bool $reviewFirst): void
    {
        $s = $this->db->prepare(
            'UPDATE users SET display_name = ?, bio = ?, accepting = ?, review_first = ? WHERE id = ?'
        );
        $s->execute([$displayName, $bio, (int) $accepting, (int) $reviewFirst, $id]);
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }
}
