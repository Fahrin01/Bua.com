<?php
/**
 * BUA.COM - Database Connection
 * Uses PDO with prepared statements (mysql)
 */

class Database
{
    private static ?PDO $instance = null;

    // ---- EDIT THESE IF YOUR XAMPP SETUP DIFFERS ----
    private static string $host = 'localhost';
    private static string $dbname = 'bua_com';
    private static string $username = 'root';
    private static string $password = '';
    // -------------------------------------------------

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$dbname . ';charset=utf8mb4';
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
            }
        }
        return self::$instance;
    }
}
