<?php
declare(strict_types=1);

namespace NF;

final class DbSessionHandler implements \SessionHandlerInterface
{
    public function open(string $path, string $name): bool { return true; }
    public function close(): bool { return true; }

    public function read(string $id): string|false
    {
        $row = Database::q(
            'SELECT payload FROM sessions WHERE id = ? AND last_activity > ? LIMIT 1',
            [$id, time() - (int)($_ENV['SESSION_LIFETIME'] ?? 1800)]
        )->fetch();
        return $row ? (string)$row['payload'] : '';
    }

    public function write(string $id, string $data): bool
    {
        Database::q(
            'INSERT INTO sessions (id, user_id, ip, user_agent, payload, last_activity)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
               user_id = VALUES(user_id),
               payload = VALUES(payload),
               last_activity = VALUES(last_activity)',
            [
                $id,
                $_SESSION['uid'] ?? null,
                Security::ipBin(),
                mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                $data,
                time(),
            ]
        );
        return true;
    }

    public function destroy(string $id): bool
    {
        Database::q('DELETE FROM sessions WHERE id = ?', [$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        Database::q('DELETE FROM sessions WHERE last_activity < ?', [time() - $max_lifetime]);
        return 0;
    }
}
