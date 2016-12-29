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
        $plugins = [];
        $sSQL = "SELECT * FROM __plugins WHERE enabled = 1";
        $st = self::$database->query($sSQL);
        $plugins = $st->fetchAll(\PDO::FETCH_ASSOC);
        return $plugins;
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
    public static function activate($plugin){
        $p = self::getAll();
        foreach($p as $plug){
            if ($plug['dir'] == $plugin){
                // Set enable
                return true;
            }
        }
    }

    /**
    * Deactivates an installed plugin
    *
    * @param String $plugin the plugin to deactivate
    * @return bool
    *
    */
    public static function deactivate($plugin){

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

    public static function get($plugin){
        
    }
}