<?php
declare(strict_types=1);

namespace NF;

final class Thread
{
    public static function slugify(string $title): string
    {
        $s = strtolower($title);
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
        $s = trim($s, '-');
        $s = mb_substr($s, 0, 60);
        return ($s !== '' ? $s : 'thread') . '-' . bin2hex(random_bytes(3));
    }

    public static function bySlug(string $slug): ?array
    {
        $row = Database::q(
            // TAMBAHKAN u.avatar_path DAN u.role DI SINI
            'SELECT t.*, u.username, u.avatar_initial, u.avatar_path, u.role, u.created_at AS user_joined,
                    (SELECT COUNT(*) FROM posts   WHERE user_id = u.id AND is_deleted = 0) AS user_post_count,
                    (SELECT COUNT(*) FROM threads WHERE user_id = u.id AND is_deleted = 0) AS user_thread_count,
                    f.name AS forum_name, f.icon AS forum_icon, f.slug AS forum_slug,
                    c.slug AS cat_slug, c.name AS cat_name, c.icon AS cat_icon
               FROM threads t
               JOIN users u      ON u.id = t.user_id
               JOIN forums f     ON f.id = t.forum_id
               JOIN categories c ON c.id = f.category_id
              WHERE t.slug = ? AND t.is_deleted = 0
              LIMIT 1',
            [$slug]
        )->fetch();
        return $row ?: null;
    }

    public static function incrementViews(int $id): void
    {
        $key = 'viewed_thread_' . $id;
        if (!empty($_SESSION[$key])) return;
        Database::q('UPDATE threads SET views = views + 1 WHERE id = ?', [$id]);
        $_SESSION[$key] = true;
    }

    public static function create(int $forumId, int $userId, string $title, string $body): string
    {
        $slug = self::slugify($title);
        Database::q(
            'INSERT INTO threads (forum_id, user_id, title, slug, body, last_post_by)
             VALUES (?,?,?,?,?,?)',
            [$forumId, $userId, $title, $slug, $body, $userId]
        );
        Audit::log('thread.create', 'thread', (int)Database::conn()->lastInsertId());
        return $slug;
    }

    public static function addReply(int $threadId, int $userId, string $body): int
    {
        $pdo = Database::conn();
        $pdo->beginTransaction();
        try {
            Database::q(
                'INSERT INTO posts (thread_id, user_id, body) VALUES (?,?,?)',
                [$threadId, $userId, $body]
            );
            $postId = (int)$pdo->lastInsertId();
            Database::q(
                'UPDATE threads
                    SET reply_count = reply_count + 1,
                        last_post_at = NOW(),
                        last_post_by = ?
                  WHERE id = ?',
                [$userId, $threadId]
            );
            $pdo->commit();
            Audit::log('post.create', 'post', $postId, ['thread_id' => $threadId]);
            return $postId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function replies(int $threadId, int $page = 1, int $perPage = 20): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        return Database::q(
            // TAMBAHKAN u.avatar_path DAN u.role DI SINI
            "SELECT p.*, u.username, u.avatar_initial, u.avatar_path, u.role, u.created_at AS user_joined,
                    (SELECT COUNT(*) FROM posts WHERE user_id = u.id AND is_deleted = 0) AS user_post_count
               FROM posts p
               JOIN users u ON u.id = p.user_id
              WHERE p.thread_id = ? AND p.is_deleted = 0
           ORDER BY p.created_at ASC
              LIMIT $perPage OFFSET $offset",
            [$threadId]
        )->fetchAll();
    }

    public static function byUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        return Database::q(
            "SELECT t.id, t.title, t.slug, t.reply_count, t.views,
                    t.created_at, t.last_post_at,
                    f.name AS forum_name, f.icon AS forum_icon,
                    c.slug AS cat_slug, c.name AS cat_name, c.icon AS cat_icon
               FROM threads t
               JOIN forums f     ON f.id = t.forum_id
               JOIN categories c ON c.id = f.category_id
              WHERE t.user_id = ? AND t.is_deleted = 0
           ORDER BY t.created_at DESC
              LIMIT $perPage OFFSET $offset",
            [$userId]
        )->fetchAll();
    }

    public static function countByUser(int $userId): int
    {
        return (int) Database::q(
            'SELECT COUNT(*) FROM threads WHERE user_id = ? AND is_deleted = 0',
            [$userId]
        )->fetchColumn();
    }
}
