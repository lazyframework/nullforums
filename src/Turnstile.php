<?php
declare(strict_types=1);

namespace NF;

final class Turnstile
{
    public static function verify(?string $response): bool
    {
        if (empty($response)) return false;

        $secret = $_ENV['TURNSTILE_SECRET_KEY'] ?? '';
        if ($secret === '') return false;

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret'   => $secret,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) return false;

        $data = json_decode($result, true);
        return isset($data['success']) && $data['success'] === true;
    }
}
