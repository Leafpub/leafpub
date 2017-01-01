<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use DirectoryIterator,
    ZipArchive,
    Composer\Semver\Comparator;

/**
* Plugin
*
* methods for working with plugins
* @package Leafpub
*
**/
class Plugin extends Leafpub {
    
    /**
    * Constants
    **/
    const
        ALREADY_EXISTS = 1,
        INVALID_NAME = 2,
        INVALID_DIR = 3,
        NOT_FOUND = 4,
        VERSION_MISMATCH = 5;

    public static $plugins;
    
    /**
    * Returns a list of all available plugins
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
    * Returns a list of all activated Plugins
    *
    * @return mixed
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
    public static function install($zipFile){
        $bPluginJson = false;
        $bPluginPhp = false;
        $bUpdate = false;
        
        $zip = new ZipArchive();
        $res = $zip->open($zipFile, ZipArchive::CHECKCONS);
        
        // Check if zip is ok
        if ($res !== TRUE) {
            switch($res) {
                case ZipArchive::ER_NOZIP:
                    throw new \Exception('not a zip archive');
                case ZipArchive::ER_INCONS :
                    throw new \Exception('consistency check failed');
                case ZipArchive::ER_CRC :
                    throw new \Exception('checksum failed');
                default:
                    throw new \Exception('error ' . $res);
            }
        }
        
        // Check for the mandatory files
        for( $i = 0; $i < $zip->numFiles; $i++ ){
            $file = $zip->getNameIndex( $i );
            if (strstr($file, 'plugin.json')){
                $bPluginJson = true; 
            }
            if (strstr($file, 'Plugin.php')){
                $bPluginPhp = true;
            }
        } 
        
        // All mandatory files are present
        if (!$bPluginJson || !$bPluginPhp){
            throw new \Exception('Mandatory file missing');
        }
        
        // Get the plugin folder
        $ns = $zip->getNameIndex(0);
        
        // Plugin exists already
        if (is_dir(self::path('content/plugins/' . $ns))){
            // We're doing an update'
            self::removeDir(self::path('content/plugins/' . $ns));
            $bUpdate = true;
        }
        
        if (!$zip->extractTo(self::path('content/plugins'))){
            throw new \Exception('Unable to extract zip');
        }
        
        $plugin = json_decode(
            file_get_contents(
                self::path("content/plugins/$ns/plugin.json")
            ), true
        );
        $plugin['dir'] = $ns;
        
        if ($bUpdate){
            $res = self::edit($plugin);
        } else {
            $res = self::add($plugin);
        }

        return $res;
    }

    /**
    * Delete the plugin folder
    *
    * @param String $plugin the plugin to deinstall
    * @return bool
    *
    */
    public static function deinstall($plugin){
        $plugin = self::get($plugin);
        if (!$plugin){
            return false;
        }

        if ($plugin['enabled'] == 1){
            self::deactivate($plugin['dir']);
        }

        self::delete($plugin['dir']);

        parent::removeDir(self::path('content/plugins/' . $plugin['dir']));

        return true;
    }

    /**
    * Activates an installed plugin
    *
    * @param String $dir the plugin to activate
    * @return bool
    *
    */
    public static function activate($dir){
        $plugin = self::get($dir);
        if (!$plugin){
            $plugin = json_decode(
                file_get_contents(
                    self::path("content/plugins/$dir/plugin.json")
                ), true
            );
            $plugin['dir'] = $dir;
            try{
                self::add($plugin);
            } catch (\Exception $e){
                echo $e->getMessage();
                return false;
            }
        }

        // Create Plugin and call _afterActivation
        $plug = "Leafpub\\Plugins\\$dir\\Plugin";
        $plug::afterActivation();

        // Update database
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

        // Get the plugin instance from the static array and call _afterDeactivation
        $plug = "Leafpub\\Plugins\\$dir\\Plugin";
        $plug::afterDeactivation();
        unset(Plugin::$plugins[$dir]);

        // Update database
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

    /**
    * Returns an array of available and activated plugins
    *
    * @return array
    *
    */
    public static function getMergedPlugins($options = null){
         $options = array_merge([
            'items_per_page' => 10,
            'page' => 1,
            'query' => null,
            'sort' => 'DESC'
        ], (array) $options);

        try {
            // Get count of all matching rows
            $st = self::$database->query('SELECT COUNT(*) FROM __plugins');
            $total_items = (int) $st->fetch()[0];
        } catch(\PDOException $e) {
            return false;
        }

        // Generate pagination
        $pagination = self::paginate(
            $total_items,
            $options['items_per_page'],
            $options['page']
        );

        $offset = ($pagination['current_page'] - 1) * $pagination['items_per_page'];
        $count = $pagination['items_per_page'];

        $sSQL = 'SELECT 
                   * 
                 FROM 
                   __plugins 
                 WHERE 
                   enabled = 0
                 UNION
                 SELECT 
                   *
                 FROM
                   __plugins
                 WHERE
                   enabled = 1
                 ORDER BY
                   name ' . $options['sort'];

        $limit_sql = ' LIMIT :offset, :count';

        $st = self::$database->prepare($sSQL . $limit_sql);
        $st->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $st->bindParam(':count', $count, \PDO::PARAM_INT);
        $st->execute();
        $databasePlugins = $st->fetchAll(\PDO::FETCH_ASSOC);

        // Merge plugins from database with plugins from filesystem...
        $plugins = array_map(
                        function($arr) use($databasePlugins){
                            foreach($databasePlugins as $plugin){
                                if ($plugin['dir'] == $arr['dir']){
                                    $arr['install_date'] = $plugin['install_date'];
                                    if ($plugin['enabled'] == 1){
                                        $arr['enable_date'] = $plugin['enable_date'];
                                    }
                                    return $arr;
                                }
                            }
                            return $arr;
                        },
                        self::getAll()
                    );
        
        foreach ($plugins as $key => $plugin){
            $plugins[$key] = self::normalize($plugin);
        }

        return $plugins;
        
    }

    /**
    * Normalize types for certain fields
    *
    * @param array $plugin
    * @return array
    *
    */
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

    /**
    * Retrieve a plugin
    *
    * @param String $plugin
    * @return mixed
    *
    */
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
           if (!$plugin){
               return false;
           }
       } catch(\PDOException $e) {
           return false;
       }

       $plugin = self::normalize($plugin);
       return $plugin;
    }

    /**
    * Adds a plugin to database
    *
    * @param array $plugin
    * @return mixed
    * @throws Exception
    *
    */
    public static function add($plugin){

        // Is the name valid?
        if(!mb_strlen($plugin['name']) || self::isProtectedSlug($plugin['name'])) {
            throw new \Exception('Invalid name: ' . $plugin['name'], self::INVALID_NAME);
        }

        if(
            !mb_strlen($plugin['dir']) || 
            self::isProtectedSlug($plugin['dir']) || 
            !is_dir(self::path('content/plugins/' . $plugin['dir']))
        ) {
            throw new \Exception('Invalid dir: ' . $plugin['dir'], self::INVALID_DIR);
        }

        if (LEAFPUB_VERSION != '{{version}}'){
            if (!Comparator::greaterThanOrEqualTo(LEAFPUB_VERSION, $plugin['requires'])){
                throw new \Exception(
                    'Plugin needs Leafpub Version ' . $plugin['requires'] . ', but version ' . LEAFPUB_VERSION . ' detected', 
                    self::VERSION_MISMATCH
                );
            }
        }

        $plugin = self::normalize($plugin);

        try {
           // Get a plugin from database
           $st = self::$database->prepare('
               INSERT INTO __plugins SET
                 name = :name,
                 description = :description,
                 author = :author,
                 version = :version,
                 dir = :dir,
                 img = :img,
                 link = :link,
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
           $st->bindParam(':img', $plugin['img']);
           $st->bindParam(':link', $plugin['link']);
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

    /**
    * Update the database entry
    *
    * @param $plugin
    * @return bool
    * @throws Exception
    *
    */
    public static function edit($plugin){
        // Is the name valid?
        if(!mb_strlen($plugin['name']) || self::isProtectedSlug($plugin['name'])) {
            throw new \Exception('Invalid name: ' . $plugin['name'], self::INVALID_NAME);
        }

        if(
            !mb_strlen($plugin['dir']) || 
            self::isProtectedSlug($plugin['dir']) || 
            !is_dir(self::path('content/plugins/' . $plugin['dir']))
        ) {
            throw new \Exception('Invalid dir: ' . $plugin['dir'], self::INVALID_DIR);
        }

        if (LEAFPUB_VERSION != '{{version}}'){
            if (!Comparator::greaterThanOrEqualTo(LEAFPUB_VERSION, $plugin['requires'])){
                throw new \Exception(
                    'Plugin needs Leafpub Version ' . $plugin['requires'] . ', but version ' . LEAFPUB_VERSION . ' detected', 
                    self::VERSION_MISMATCH
                );
            }
        }

        try {
           // Get a plugin from database
           $st = self::$database->prepare('
               UPDATE __plugins SET
                 name = :name,
                 description = :description,
                 author = :author,
                 version = :version,
                 dir = :dir,
                 img = :img,
                 link = :link,
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
           $st->bindParam(':img', $plugin['img']);
           $st->bindParam(':link', $plugin['link']);
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

    /**
    * Delete a database entry
    *
    * @param String $dir
    * @return bool
    * 
    */
    public static function delete($dir){
        try {
           // Get a plugin from database
           $st = self::$database->prepare('
               DELETE FROM __plugins
               WHERE dir = :dir
           ');
           $st->bindParam(':dir', $dir);
           $st->execute();
           return ($st->rowCount() > 0);
       } catch(\PDOException $e) {
           return false;
       }
    }

    /**
    * Checks, if a plugin exists
    *
    * @param String $dir
    * @return bool
    *
    */
    public static function exists($dir){
        try {
           // Get a plugin from database
           $st = self::$database->prepare('
               SELECT * FROM __plugins
               WHERE dir = :dir
               ORDER BY name
           ');
           $st->bindParam(':dir', $dir);
           $st->execute();
           return !!$st->fetch(\PDO::FETCH_ASSOC);
       } catch(\PDOException $e) {
           return false;
       }
    }
}