<?php
declare(strict_types=1);

namespace NF;

final class Auth
{
    private const MAX_FAIL = 5; // Maksimal gagal
    private const LOCK_MIN = 15; // Blokir selama 15 menit

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => (int)($_ENV['ARGON2_MEMORY']  ?? 65536),
            'time_cost'   => (int)($_ENV['ARGON2_TIME']    ?? 4),
            'threads'     => (int)($_ENV['ARGON2_THREADS'] ?? 2),
        ]);
    }

    public static function user(): ?array
    {
        if (empty($_SESSION['uid'])) return null;
        return Database::q(
            'SELECT id, username, email, role, avatar_initial, avatar_path
               FROM users WHERE id = ? AND is_active = 1 LIMIT 1',
            [$_SESSION['uid']]
        )->fetch() ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        return Database::q(
            'SELECT id, username, role, avatar_initial, avatar_path,created_at,
                    (SELECT COUNT(*) FROM threads WHERE user_id = users.id AND is_deleted = 0) AS thread_count,
                    (SELECT COUNT(*) FROM posts   WHERE user_id = users.id AND is_deleted = 0) AS post_count
               FROM users
              WHERE username = ? AND is_active = 1
              LIMIT 1',
            [$username]
        )->fetch() ?: null;
    }

    public static function login(string $login, string $password): array
    {
        $ip = Security::ipBin();

        // 1. CEK BLOKIR IP (Anti Brute Force)
        $failedCount = (int) Database::q(
            'SELECT COUNT(*) FROM login_attempts
              WHERE ip = ? AND success = 0 
                AND created_at > (NOW() - INTERVAL ? MINUTE)',
            [$ip, self::LOCK_MIN]
        )->fetchColumn();

        if ($failedCount >= self::MAX_FAIL) {
            return [false, 'Akses diblokir sementara karena terlalu banyak percobaan gagal dari IP Anda.'];
        }

        // 2. PROSES LOGIN
        $u = Database::q(
            'SELECT id, username, password_hash, role, is_active,
                    failed_logins, locked_until
               FROM users WHERE username = ? OR email = ? LIMIT 1',
            [$login, $login]
        )->fetch();

        $success = false;
        if ($u && (int)$u['is_active'] === 1) {
            if ($u['locked_until'] && strtotime($u['locked_until']) > time()) {
                self::logAttempt($ip, $login, false);
                return [false, 'Akun terkunci sementara.'];
            }
            if (password_verify($password, $u['password_hash'])) {
                $success = true;
                if (password_needs_rehash($u['password_hash'], PASSWORD_ARGON2ID)) {
                    Database::q('UPDATE users SET password_hash = ? WHERE id = ?',
                        [self::hash($password), $u['id']]);
                }
                Database::q(
                    'UPDATE users SET failed_logins = 0, locked_until = NULL,
                            last_login_at = NOW(), last_login_ip = ? WHERE id = ?',
                    [$ip, $u['id']]
                );
            } else {
                $fails = (int)$u['failed_logins'] + 1;
                $lock  = $fails >= self::MAX_FAIL
                    ? date('Y-m-d H:i:s', time() + self::LOCK_MIN * 60) : null;
                Database::q('UPDATE users SET failed_logins = ?, locked_until = ? WHERE id = ?',
                    [$fails, $lock, $u['id']]);
            }
        }

        self::logAttempt($ip, $login, $success);
        if (!$success) return [false, 'Kredensial salah.'];
        
        // Jika sukses, hapus riwayat gagal dari IP ini agar bersih
        Database::q('DELETE FROM login_attempts WHERE ip = ? AND success = 0', [$ip]);

        session_regenerate_id(true);
        $_SESSION['uid']      = (int)$u['id'];
        $_SESSION['role']     = $u['role'];
        $_SESSION['last_act'] = time();
        return [true, 'OK'];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    private static function logAttempt(string $ip, string $user, bool $ok): void
    {
        Database::q(
            'INSERT INTO login_attempts (ip, username, success) VALUES (?,?,?)',
            [$ip, mb_substr($user, 0, 32), (int)$ok]
        );
    }
}
