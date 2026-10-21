<?php
declare(strict_types=1);

return [
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $_ENV['DB_HOST'],
        $_ENV['DB_PORT'],
        $_ENV['DB_NAME'],
        $_ENV['DB_CHARSET']
    ),
    'user' => $_ENV['DB_USER'],
    'pass' => $_ENV['DB_PASS'],
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ],
];
