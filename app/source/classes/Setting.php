<?php
//
// Postleaf\Settings: methods for working with settings
//
// Note: settings are cached in the static $settings property of the base class in order to reduce
// superfluous database calls.
//
namespace Postleaf;

class Setting extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Adds a setting
    public static function add($name, $value) {
        return self::update($name, $value);
    }

    // Gets a single setting
    public static function get($name = null) {
        return isset(self::$settings[$name]) ? self::$settings[$name] : null;
    }

    // Returns all settings in an array
    public static function getAll() {
        return self::$settings;
    }

    // Delete a setting
    public static function delete($name) {
        // Delete from the database
        try {
            $st = self::$database->prepare('DELETE FROM __settings WHERE name = :name');
            $st->bindParam(':name', $name);
            $st->execute();
        } catch(\PDOException $e) {
            return false;
        }

        unset(self::$settings[$name]);

        return true;
    }

    // Load settings from the database and store in a static variable for quick access
    public static function load() {
        try {
            $st = self::$database->query('SELECT name, value FROM __settings ORDER BY name');
            $st->fetchAll(\PDO::FETCH_FUNC, function($name, $value) {
                self::$settings[$name] = $value;
            });
        } catch(\PDOException $e) {
            throw new \Exception('Unable to load settings from the database.');
        }
    }

    // Update a setting
    public static function update($name, $value) {
        // Update the database
        try {
            $st = self::$database->prepare('
                REPLACE INTO __settings
                SET name = :name, value = :value
            ');
            $st->bindParam(':name', $name);
            $st->bindParam(':value', $value);
            $st->execute();
        } catch(\PDOException $e) {
            return false;
        }
        // Update cache
        self::$settings[$name] = $value;

        return true;
    }

}