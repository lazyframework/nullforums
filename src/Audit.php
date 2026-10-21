<?php
declare(strict_types=1);

namespace NF;

final class Audit
{
    public static function log(
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        ?array $meta = null
    ): void {
        Database::q(
            'INSERT INTO audit_log (user_id, action, entity, entity_id, ip, meta)
             VALUES (?,?,?,?,?,?)',
            [
                $_SESSION['uid'] ?? null,
                mb_substr($action, 0, 64),
                $entity,
                $entityId,
                Security::ipBin(),
                $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }
}
