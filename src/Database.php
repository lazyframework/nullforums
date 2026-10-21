<?php
declare(strict_types=1);

namespace NF;

use PDO;
use PDOStatement;

final class Database
{
    private static ?PDO $pdo = null;

    public static function conn(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }
        $cfg = require __DIR__ . '/../config/database.php';
        self::$pdo = new PDO($cfg['dsn'], $cfg['user'], $cfg['pass'], $cfg['options']);
        self::$pdo->exec("SET time_zone = '+07:00'");
        return self::$pdo;
    }

    public static function q(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::conn()->prepare($sql);
        foreach ($params as $k => $v) {
            $key  = is_int($k) ? $k + 1 : ':' . ltrim((string)$k, ':');
            $type = match (true) {
                is_int($v)  => PDO::PARAM_INT,
                is_bool($v) => PDO::PARAM_BOOL,
                is_null($v) => PDO::PARAM_NULL,
                default     => PDO::PARAM_STR,
            };
            $stmt->bindValue($key, $v, $type);
        }
        $stmt->execute();
        return $stmt;
    }
}
