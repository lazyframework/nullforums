<?php
declare(strict_types=1);

namespace NF;

final class Security
{
    public static function headers(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' https://challenges.cloudflare.com; " .
    "frame-src https://challenges.cloudflare.com; " .
    "connect-src 'self' https://challenges.cloudflare.com; " .
    "img-src 'self' data:; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
    "font-src 'self' https://fonts.gstatic.com; " .
    "frame-ancestors 'none'; " .
    "base-uri 'self'; " .
    "form-action 'self'"
);
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    public static function e(?string $s): string
    {
        return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function ipBin(): string
    {
        return inet_pton($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') ?: "\0\0\0\0";
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function icon(?string $icon): string
{
    $icon = trim((string)$icon);
    if ($icon === '') return '';
    if (str_starts_with($icon, 'ti ')) {
        return '<i class="' . self::e($icon) . '"></i>';
    }
    return self::e($icon);
}
}
