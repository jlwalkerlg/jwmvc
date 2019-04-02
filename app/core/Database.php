<?php

/**
 * Core database class.
 */
class Database
{
    private static $host = DB_HOST;
    private static $user = DB_USER;
    private static $pass = DB_PASS;
    private static $name = DB_NAME;

    /** @var object $dbh PDO instance. */
    protected static $dbh;


    /**
     * Store PDO instance on class if not already instantiated.
     */
    protected static function set_instance()
    {
        // If instance already set, don't instantiate it again.
        if (isset(self::$dbh) && self::$dbh instanceof PDO) {
            return;
        }

        // Construct DSN.
        $dsn = 'mysql:host=' . self::$host . ';dbname=' . self::$name . ';charset=utf8';

        // Options.
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
        ];

        // Create new PDO instance.
        try {
            self::$dbh = new PDO($dsn, self::$user, self::$pass, $options);
        } catch (PDOException $e) {
            exit('Failed to connect to database.');
        }
    }

    /**
     * Get database handle instance.
     *
     * Useful for beginning transactions etc.
     *
     * @return PDO PDO database handle instance.
     */
    public static function get_instance()
    {
        self::set_instance();
        return self::$dbh;
    }
}
