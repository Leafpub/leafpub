<?php
//
// Postleaf\Database: methods for working with the database
//
// Note: the database instance is stored in the static $database property of the base class to
// prevent superflous database connections.
//
namespace Postleaf;

class Database extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Constants
    ////////////////////////////////////////////////////////////////////////////////////////////////

    const
        AUTH_ERROR = 1,
        CONNECT_ERROR = 2,
        DOES_NOT_EXIST = 3,
        INIT_FAILED = 4,
        NOT_CONFIGURED = 5,
        TIMEOUT = 6;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    public static function connect($config = null, $pdo_options = []) {
        // Load default database config
        if(!$config) {
            $file = self::path('database.php');
            if(file_exists($file)) {
                $config = include $file;
            } else {
                throw new \Exception('Database not configured', self::NOT_CONFIGURED);
            }
        }

        // Merge PDO options
        $pdo_options = array_merge([
            \PDO::MYSQL_ATTR_FOUND_ROWS => true
        ], $pdo_options);

        // Connect to the database
        try {
            self::$database = new PostleafPDO(
                (
                    "mysql:host={$config[host]};" .
                    "port={$config[port]};" .
                    "dbname={$config[database]};" .
                    "charset=utf8mb4"
                ),
                $config['user'],
                $config['password'],
                $pdo_options,
                $config['prefix']
            );
            self::$database->exec('SET time_zone = "+00:00"');
        } catch(\PDOException $e) {
            switch($e->getCode()) {
                case 1044: // Access denied for database
                case 1045: // Access denied for user
                    $message = 'The database rejected this user or password. Make sure the user exists and has access to the specified database.';
                    $code = self::AUTH_ERROR;
                    break;

                case 1049: // Unknown database
                    $message = 'The specified database does not exist.';
                    $code = self::DOES_NOT_EXIST;
                    break;

                case 2002: // Timed out
                    $message = 'The database is not responding. Is the host correct?';
                    $code = self::TIMEOUT;
                    break;

                default: // Other
                    $message = $e->getMessage();
                    $code = self::CONNECT_ERROR;
            }

            throw new \Exception($message, $code);
        }
    }

    // Escapes % and _ characters which are wildcards for LIKE
    public static function escapeLikeWildcards($string) {
        $string = str_replace('%', '\\%', $string);
        $string = str_replace('_', '\\_', $string);
        return $string;
    }

    // Drops all Postleaf database tables and recreates them from default.database.sql
    public static function resetTables() {
        try {
            self::$database->exec(file_get_contents(Postleaf::path('source/defaults/default.database.sql')));
        } catch(\PDOException $e) {
            throw new \Exception(
                'Unable to create database schema: ' . $e->getMessage(),
                self::INIT_FAILED
            );
        }
    }

}