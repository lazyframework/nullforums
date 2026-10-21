<?php
declare(strict_types=1);

namespace NF;

final class Acl
{
    public static function requireLogin(): array
    {
        $u = Auth::user();
        if (!$u) { header('Location: /login'); exit; }
        return $u;
    }

    public static function requireRole(string ...$roles): array
    {
        $u = self::requireLogin();
        if (!in_array($u['role'], $roles, true)) {
            http_response_code(403);
            exit('Akses ditolak.');
        }
        return $u;
    }
}
