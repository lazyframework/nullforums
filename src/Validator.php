<?php
declare(strict_types=1);

namespace NF;

final class Validator
{
    public static function username(string $v): ?string
    {
        if (!preg_match('/^[a-zA-Z0-9_.]{3,32}$/', $v)) {
            return 'Username harus 3–32 karakter (huruf, angka, titik, underscore).';
        }
        return null;
    }

    public static function email(string $v): ?string
    {
        if (!filter_var($v, FILTER_VALIDATE_EMAIL)) return 'Format email tidak valid.';
        if (mb_strlen($v) > 190) return 'Email terlalu panjang.';
        return null;
    }

    public static function password(string $v): ?string
    {
        if (strlen($v) < 10)            return 'Kata sandi minimal 10 karakter.';
        if (!preg_match('/[A-Z]/', $v)) return 'Kata sandi harus mengandung huruf besar.';
        if (!preg_match('/[a-z]/', $v)) return 'Kata sandi harus mengandung huruf kecil.';
        if (!preg_match('/[0-9]/', $v)) return 'Kata sandi harus mengandung angka.';
        return null;
    }

    public static function title(string $v): ?string
    {
        $len = mb_strlen(trim($v));
        if ($len < 5)   return 'Judul minimal 5 karakter.';
        if ($len > 180) return 'Judul maksimal 180 karakter.';
        return null;
    }

    public static function body(string $v, int $min = 10, int $max = 20000): ?string
    {
        $len = mb_strlen(trim($v));
        if ($len < $min) return "Isi minimal $min karakter.";
        if ($len > $max) return "Isi maksimal $max karakter.";
        return null;
    }
}
