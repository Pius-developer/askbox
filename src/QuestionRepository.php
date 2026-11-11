<?php
declare(strict_types=1);

final class QuestionRepository
{
    public function __construct(private PDO $db) {}

    public function find(int $id): ?array
    {
        $s = $this->db->prepare(
            'SELECT q.*, u.username, u.display_name FROM questions q
             JOIN users u ON u.id = q.user_id WHERE q.id = ?'
        );
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function ask(int $userId, string $body, string $askerHash, bool $hidden): int
    {
        $s = $this->db->prepare(
            'INSERT INTO questions (user_id, body, asker_hash, is_hidden) VALUES (?, ?, ?, ?)'
        );
        $s->execute([$userId, $body, $askerHash, (int) $hidden]);
        return (int) $this->db->lastInsertId();
    }

    /** How many questions this visitor sent to this box in the last N minutes. */
    public function recentCount(int $userId, string $askerHash, int $minutes): int
    {
        $s = $this->db->prepare(
            "SELECT COUNT(*) FROM questions
             WHERE user_id = ? AND asker_hash = ? AND created_at > datetime('now', ?)"
        );
        $s->execute([$userId, $askerHash, '-' . $minutes . ' minutes']);
        return (int) $s->fetchColumn();
    }

    /** Public answered feed: pinned first, then newest answers. */
    public function answered(int $userId): array
    {
        $s = $this->db->prepare(
            'SELECT * FROM questions WHERE user_id = ? AND answer IS NOT NULL AND is_hidden = 0
             ORDER BY is_pinned DESC, answered_at DESC LIMIT 100'
        );
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    /** Public "up next" queue: unanswered, visible, most upvoted first. */
    public function upNext(int $userId): array
    {
        $s = $this->db->prepare(
            'SELECT * FROM questions WHERE user_id = ? AND answer IS NULL AND is_hidden = 0
             ORDER BY upvotes DESC, created_at DESC LIMIT 100'
        );
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    /** Owner inbox: everything, unanswered first. */
    public function inbox(int $userId, bool $answered): array
    {
        $cond = $answered ? 'answer IS NOT NULL' : 'answer IS NULL';
        $order = $answered ? 'answered_at DESC' : 'upvotes DESC, created_at DESC';
        $s = $this->db->prepare("SELECT * FROM questions WHERE user_id = ? AND $cond ORDER BY $order LIMIT 200");
        $s->execute([$userId]);
        return $s->fetchAll();
    }

    public function stats(int $userId): array
    {
        $s = $this->db->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(answer IS NOT NULL) AS answered,
                    COALESCE(SUM(upvotes), 0) AS upvotes
             FROM questions WHERE user_id = ?'
        );
        $s->execute([$userId]);
        $r = $s->fetch();
        return array_map('intval', $r);
    }

    /** @return int|null new upvote count, or null if this visitor already voted */
    public function upvote(int $questionId, string $voterHash): ?int
    {
        $this->db->beginTransaction();
        try {
            $s = $this->db->prepare('INSERT OR IGNORE INTO upvotes (question_id, voter_hash) VALUES (?, ?)');
            $s->execute([$questionId, $voterHash]);
            $added = $s->rowCount() === 1;
            if ($added) {
                $this->db->prepare('UPDATE questions SET upvotes = upvotes + 1 WHERE id = ?')->execute([$questionId]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $added ? (int) $this->find($questionId)['upvotes'] : null;
    }

    /** Question ids (from a list) this visitor already upvoted. */
    public function votedIds(array $questionIds, string $voterHash): array
    {
        if (!$questionIds) {
            return [];
        }
        $in = implode(',', array_fill(0, count($questionIds), '?'));
        $s = $this->db->prepare("SELECT question_id FROM upvotes WHERE voter_hash = ? AND question_id IN ($in)");
        $s->execute([$voterHash, ...$questionIds]);
        return array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    }

    public function answer(int $id, string $answer): void
    {
        $s = $this->db->prepare(
            "UPDATE questions SET answer = ?, answered_at = COALESCE(answered_at, datetime('now')), is_hidden = 0 WHERE id = ?"
        );
        $s->execute([$answer, $id]);
    }

    public function toggle(int $id, string $field): void
    {
        if (!in_array($field, ['is_hidden', 'is_pinned'], true)) {
            throw new InvalidArgumentException('Bad field');
        }
        $this->db->prepare("UPDATE questions SET $field = 1 - $field WHERE id = ?")->execute([$id]);
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM questions WHERE id = ?')->execute([$id]);
    }
}
