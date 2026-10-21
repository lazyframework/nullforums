<?php
declare(strict_types=1);

namespace NF;

final class Throttle
{
    public static function allow(int $userId, string $action, int $max, int $minutes): bool
    {
        $count = (int) Database::q(
            'SELECT COUNT(*) FROM action_throttle
              WHERE user_id = ? AND action = ?
                AND created_at > (NOW() - INTERVAL ? MINUTE)',
            [$userId, $action, $minutes]
        )->fetchColumn();

        if ($count >= $max) return false;

        Database::q(
            'INSERT INTO action_throttle (user_id, action) VALUES (?,?)',
            [$userId, $action]
        );
        return true;
    }
}
