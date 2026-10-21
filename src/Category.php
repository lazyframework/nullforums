<?php
declare(strict_types=1);

namespace NF;

final class Category
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) return self::$cache;
        self::$cache = Database::q(
            'SELECT id, slug, name, icon, description
               FROM categories
              WHERE is_active = 1
           ORDER BY sort_order ASC'
        )->fetchAll();
        return self::$cache;
    }

    public static function bySlug(string $slug): ?array
    {
        foreach (self::all() as $c) {
            if ($c['slug'] === $slug) return $c;
        }
        return null;
    }

    public static function stats(int $categoryId): array
    {
        return Database::q(
            'SELECT
                COUNT(DISTINCT t.id)            AS threads,
                COALESCE(SUM(t.reply_count),0)  AS replies,
                COALESCE(SUM(t.views),0)        AS views
               FROM threads t
               JOIN forums f ON f.id = t.forum_id
              WHERE f.category_id = ? AND t.is_deleted = 0',
            [$categoryId]
        )->fetch() ?: ['threads' => 0, 'replies' => 0, 'views' => 0];
    }

    public static function forums(int $categoryId, string $range = 'all'): array
    {
        $rangeSql = match ($range) {
            'today' => 'AND t.last_post_at >= CURDATE()',
            'week'  => 'AND t.last_post_at >= (NOW() - INTERVAL 7 DAY)',
            'month' => 'AND t.last_post_at >= (NOW() - INTERVAL 30 DAY)',
            default => '',
        };

        return Database::q(
            "SELECT f.id, f.slug, f.name, f.description, f.icon,
                    COUNT(t.id)                    AS thread_count,
                    COALESCE(SUM(t.reply_count),0) AS post_count,
                    (SELECT t2.title FROM threads t2
                      WHERE t2.forum_id = f.id AND t2.is_deleted = 0
                      ORDER BY t2.last_post_at DESC LIMIT 1) AS last_title,
                    (SELECT u.username FROM threads t3
                      JOIN users u ON u.id = t3.last_post_by
                      WHERE t3.forum_id = f.id
                      ORDER BY t3.last_post_at DESC LIMIT 1) AS last_user
               FROM forums f
               LEFT JOIN threads t ON t.forum_id = f.id AND t.is_deleted = 0 $rangeSql
              WHERE f.category_id = ? AND f.is_active = 1
           GROUP BY f.id
           ORDER BY f.sort_order ASC",
            [$categoryId]
        )->fetchAll();
    }

    public static function popularThreads(int $categoryId, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        return Database::q(
            "SELECT t.id, t.title, t.slug, t.views, t.reply_count, t.last_post_at,
                    f.name AS forum_name, f.icon AS forum_icon,
                    u.username AS last_user
               FROM threads t
               JOIN forums f ON f.id = t.forum_id
               LEFT JOIN users u ON u.id = t.last_post_by
              WHERE f.category_id = ? AND t.is_deleted = 0
           ORDER BY t.reply_count DESC, t.views DESC
              LIMIT $limit",
            [$categoryId]
        )->fetchAll();
    }

    public static function latestThreads(int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        return Database::q(
            "SELECT t.title, t.slug, t.last_post_at, f.icon, c.slug AS cat_slug
               FROM threads t
               JOIN forums f     ON f.id = t.forum_id
               JOIN categories c ON c.id = f.category_id
              WHERE t.is_deleted = 0
           ORDER BY t.last_post_at DESC
              LIMIT $limit"
        )->fetchAll();
    }

    public static function onlineUsers(int $minutes = 15, int $limit = 7): array
    {
        $minutes = max(1, min(1440, $minutes));
        $limit   = max(1, min(50, $limit));
        return Database::q(
            "SELECT u.username, u.avatar_initial
               FROM sessions s JOIN users u ON u.id = s.user_id
              WHERE s.last_activity > (UNIX_TIMESTAMP() - ? * 60)
                AND s.user_id IS NOT NULL
           GROUP BY u.id
           ORDER BY MAX(s.last_activity) DESC
              LIMIT $limit",
            [$minutes]
        )->fetchAll();
    }
}
