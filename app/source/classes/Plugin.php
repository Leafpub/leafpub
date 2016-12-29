<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use DirectoryIterator;

/**
* Plugin
*
* methods for working with plugins
* @package Leafpub
*
**/
class Plugin extends Leafpub {
    
    public static $plugins;
    
    /**
    * Returns an array of all available themes
    *
    * @return array
    *
    **/
    public static function getAll() {
        $plugins = [];

        $iterator = new DirectoryIterator(self::path('content/plugins'));
        foreach($iterator as $dir) {
            if(!$dir->isDot() && $dir->isDir()) {
                // Attempt to read and decode theme.json
                $plugin_json = $dir->getPathname() . '/plugin.json';
                $json = json_decode(file_get_contents($plugin_json), true);
                if(!$json) continue;
                // Add it
                $plugins[] = array_merge($json, ['dir' => $dir->getFilename()]);
            }
        }

        return $plugins;
    }

    /**
    * Returns an array of all activated Plugins
    *
    * @return array
    *
    */
    public static function getActivatedPlugins(){
        try {
           // Get a list of slugs
           $st = self::$database->query('
               SELECT * FROM __plugins WHERE enabled = 1
           ');
           return $st->fetchAll(\PDO::FETCH_ASSOC);
       } catch(\PDOException $e) {
           return false;
       }
    }

    /**
    * Upload a zip, unzip, create a folder, copy files
    *
    * @return bool
    *
    */
    public static function install(){

    }

    /**
    * Delete the plugin folder
    *
    * @param String $plugin the plugin to deinstall
    * @return bool
    *
    */
    public static function deinstall($plugin){

    }

    /**
    * Activates an installed plugin
    *
    * @param String $plugin the plugin to activate
    * @return bool
    *
    */
    public static function activate($dir){
        $plugin = self::get($dir);
        if (!$plugin){
            $plugin = (object) json_decode(
                file_get_contents(
                    self::path("content/plugins/$dir/plugin.json")
                ), true
            );
            self::add($plugin);
        }
        try {
            $st = self::$database->prepare('
                UPDATE __plugins SET
                  enabled = 1,
                  enable_date = NOW()
                WHERE
                  1 = 1
                AND
                  dir = :dir;
            ');
            $st->bindParam(':dir', $dir);
            return $st->execute();
        } catch(\PDOException $e) {
           return false;
       }    
    }

    /**
    * Deactivates an installed plugin
    *
    * @param String $dir the plugin to deactivate
    * @return bool
    *
    */
    public static function deactivate($dir){
        $plugin = self::get($dir);
        if (!$plugin){
            return false;
        }
        try {
            $st = self::$database->prepare('
                UPDATE __plugins SET
                  enabled = 0,
                  enable_date = NOW()
                WHERE
                  1 = 1
                AND
                  dir = :dir;
            ');
            $st->bindParam(':dir', $dir);
            $st->execute();
            return ($st->rowCount() > 0);
        } catch(\PDOException $e) {
           return false;
       }   
    }

    public static function getMergedPlugins(){
        $enabledPlugins = self::getActivatedPlugins();
        $plugins = array_map(
                        function($arr) use($enabledPlugins){
                            foreach($enabledPlugins as $plugin){
                                if ($plugin['name'] == $arr['name']){
                                    $arr['install_date'] = $plugin['install_date'];
                                    if ($plugin['enabled'] == 1){
                                        $arr['enable_date'] = $plugin['enable_date'];
                                    }
                                    return $arr;
                                }
                            }
                            return $arr;
                        },
                        Plugin::getAll()
                    );

        return $plugins;
        
    }

    private static function normalize($plugin){
        // Cast to integer
        $plugin['id'] = (int) $plugin['id'];
        $plugin['isAdminPlugin'] = (int) $plugin['isAdminPlugin'];
        $plugin['isMiddleware'] = (int) $plugin['isMiddleware'];
        $plugin['enabled'] = (int) $plugin['enabled'];

        // Convert dates from UTC to local
        $plugin['install_date'] = self::utcToLocal($plugin['install_date']);
        $plugin['enable_date'] = self::utcToLocal($plugin['enable_date']);

        return $plugin;
    }

    public static function get($plugin){
        try {
           // Get a plugin from database
           $st = self::$database->prepare('
               SELECT * FROM __plugins
               WHERE dir = :dir
               ORDER BY name
           ');
           $st->bindParam(':dir', $plugin);
           $st->execute();
           $plugin = $st->fetch(\PDO::FETCH_ASSOC);
       } catch(\PDOException $e) {
           return false;
       }

       $plugin = self::normalize($plugin);
       return $plugin;
    }

    public static function add($plugin){
        try {
           // Get a plugin from database
           $st = self::$database->prepare('
               INSERT INTO __plugins SET
                 name = :name,
                 description = :description,
                 author = :author,
                 version = :version,
                 dir = :dir,
                 isAdminPlugin = :isAdminPlugin,
                 isMiddleware = :isMiddleware,
                 requires = :requires,
                 license = :license,
                 install_date = NOW()
           ');

           $st->bindParam(':name', $plugin['name']);
           $st->bindParam(':description', $plugin['description']);
           $st->bindParam(':author', $plugin['author']);
           $st->bindParam(':version', $plugin['version']);
           $st->bindParam(':dir', $plugin['dir']);
           $st->bindParam(':isAdminPlugin', $plugin['isAdminPlugin']);
           $st->bindParam(':isMiddleware', $plugin['isMiddleware']);
           $st->bindParam(':requires', $plugin['requires']);
           $st->bindParam(':license', $plugin['license']);
           $st->execute();
           
           $plugin_id = (int) self::$database->lastInsertId();
           if($plugin_id <= 0) return false;
       } catch(\PDOException $e) {
           return false;
       }

       return true;
    }

    public static function edit($plugin){
        try {
           // Get a plugin from database
           $st = self::$database->prepare('
               UPDATE __plugins SET
                 name = :name,
                 description = :description,
                 author = :author,
                 version = :version,
                 dir = :dir,
                 isAdminPlugin = :isAdminPlugin,
                 isMiddleware = :isMiddleware,
                 requires = :requires,
                 license = :license
                WHERE
                  1 = 1
                AND
                  id = :id
           ');

           $st->bindParam(':name', $plugin['name']);
           $st->bindParam(':description', $plugin['description']);
           $st->bindParam(':author', $plugin['author']);
           $st->bindParam(':version', $plugin['version']);
           $st->bindParam(':dir', $plugin['dir']);
           $st->bindParam(':isAdminPlugin', $plugin['isAdminPlugin']);
           $st->bindParam(':isMiddleware', $plugin['isMiddleware']);
           $st->bindParam(':requires', $plugin['requires']);
           $st->bindParam(':license', $plugin['license']);
           $st->bindParam(':id', $plugin['id']);
           
           $st->execute();
       } catch(\PDOException $e) {
           return false;
       }

       return true;
    }

    public static function delete($dir){
        try {
           // Get a plugin from database
           $st = self::$database->prepare('
               DELETE FROM __plugins
               WHERE dir = :dir
           ');
           $st->bindParam(':dir', $plugin);
           $st->execute();
           return ($st->rowCount() > 0);
       } catch(\PDOException $e) {
           return false;
       }
    }
}