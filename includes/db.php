<?php
/**
 * includes/db.php
 * Singleton PDO connection. DSN is pulled from environment via config.php.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $pdo = new PDO(db_dsn(), db_user(), db_pass(), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        // Always store timestamps in UTC regardless of server timezone
        $pdo->exec("SET time_zone = '+00:00'");
    } catch (PDOException $e) {
        http_response_code(500);
        if (APP_DEBUG) {
            die('DB connection failed: ' . htmlspecialchars($e->getMessage()));
        }
        die('Database unavailable. Please contact the administrator.');
    }
    return $pdo;
}
