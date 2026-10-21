<?php
declare(strict_types=1);

namespace NF;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = Security::randomToken(32);
        }
        return $_SESSION['_csrf'];
    }

    public static function check(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="'
            . Security::e(self::token()) . '">';
    }
}
