<?php
declare(strict_types=1);

if (!function_exists('relative_time')) {
    function relative_time(?string $datetime): string
    {
        if (!$datetime) return '-';
        $ts = strtotime($datetime);
        if ($ts === false) return '-';
        $diff = time() - $ts;
        if ($diff < 60)     return $diff . 's';
        if ($diff < 3600)   return floor($diff / 60) . 'm';
        if ($diff < 86400)  return floor($diff / 3600) . 'j';
        if ($diff < 604800) return floor($diff / 86400) . 'h';
        return date('d M', $ts);
    }
}
