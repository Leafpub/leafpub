<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Zend\Db\Adapter\Adapter,
    Zend\Db\TableGateway\Feature\GlobalAdapterFeature,
    Leafpub\Models\Tables\TableGateway;
/**
* Database
*
* methods for working with the database
*
* Note: the database instance is stored in the static $database property of the base class to
* prevent superflous database connections.
* @package Leafpub
*
**/
class Database extends Leafpub {

    /**
    * Constants
    **/
    const
        AUTH_ERROR = 1,
        CONNECT_ERROR = 2,
        DOES_NOT_EXIST = 3,
        INIT_FAILED = 4,
        NOT_CONFIGURED = 5,
        TIMEOUT = 6;
    
    /**
    * Connect the Database
    *
    * @param null $config
    * @param array $pdo_options
    * @return void
    * @throws \Exception
    *
    **/
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
            GlobalAdapterFeature::setStaticAdapter(new Adapter($config));
            TableGateway::$prefix = $config['prefix'];
            
            GlobalAdapterFeature::getStaticAdapter()->query('SET time_zone = "+00:00"', Adapter::QUERY_MODE_EXECUTE);
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

    /**
    * Escapes % and _ characters which are wildcards for LIKE
    *
    * @param String $string
    * @return String
    *
    **/
    public static function escapeLikeWildcards($string) {
        $string = str_replace('%', '\\%', $string);
        $string = str_replace('_', '\\_', $string);
        return $string;
    }

    /**
    * Drops all Leafpub database tables and recreates them from default.database.sql
    *
    * @return void
    * @throws \Exception
    *
    **/
    public static function resetTables(){
        $adapter = GlobalAdapterFeature::getStaticAdapter();
        $sql = new \Zend\Db\Sql\Sql($adapter);
        $isMySQL = (strtolower($adapter->getDriver()->getDatabasePlatformName()) == 'mysql');

            foreach(self::getTableNames() as $table){
                $x = '\\Leafpub\\Models\\Ddl\\' . $table;
                $ddl = new $x();
                $tableSQL = $sql->getSqlStringForSqlObject(new Models\Ddl\Drop($ddl->getTable())) . '; ';
                $tableSQL .= $sql->getSqlStringForSqlObject($ddl);
                if ($isMySQL){
                    $tableSQL .= ' ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;';
                }
                
                try {

                    $adapter->query(
                        $tableSQL,
                        Adapter::QUERY_MODE_EXECUTE
                    );

                    if ($table == 'Upload'){
                        $dbTable = new Models\Tables\Upload();
                        $dbTable->insert(['id' => 1, 'caption' => '', 'created' => '2017-02-01 12:23:45', 'path' => 'content/uploads/2016/10/', 'filename' => 'leaves', 'extension' => 'jpg', 'size' => 254734, 'width' => 3000, 'height' => 2008]);
                        $dbTable->insert(['id' => 2, 'caption' => '', 'created' => '2017-02-01 12:24:28', 'path' => 'content/uploads/2016/10/', 'filename' => 'sunflower', 'extension' => 'jpg', 'size' => 280779, 'width' => 3000, 'height' => 1990]);
                        $dbTable->insert(['id' => 3, 'caption' => '', 'created' => '2017-02-01 12:24:40', 'path' => 'content/uploads/2016/10/', 'filename' => 'autumn', 'extension' => 'jpg', 'size' => 383879, 'width' => 3000, 'height' => 2000]);
                        $dbTable->insert(['id' => 4, 'caption' => '', 'created' => '2017-02-01 12:24:54', 'path' => 'content/uploads/2016/10/', 'filename' => 'ladybug', 'extension' => 'jpg', 'size' => 277815, 'width' => 3000, 'height' => 1993]);
                        $dbTable->insert(['id' => 5, 'caption' => '', 'created' => '2017-02-01 12:24:54', 'path' => 'content/uploads/2016/10/', 'filename' => 'flowers', 'extension' => 'jpg', 'size' => 342200, 'width' => 3000, 'height' => 2000]);
                        $dbTable->insert(['id' => 6, 'caption' => '', 'created' => '2017-02-01 12:24:54', 'path' => 'content/uploads/2016/10/', 'filename' => 'leaf', 'extension' => 'jpg', 'size' => 220200, 'width' => 3000, 'height' => 2253]);
                        $dbTable->insert(['id' => 7, 'caption' => '', 'created' => '2017-02-01 12:24:54', 'path' => 'content/uploads/2016/10/', 'filename' => 'note', 'extension' => 'jpg', 'size' => 453700, 'width' => 3000, 'height' => 2000]);
                        $dbTable->insert(['id' => 8, 'caption' => '', 'created' => '2017-02-01 12:24:54', 'path' => 'content/uploads/2016/10/', 'filename' => 'light', 'extension' => 'jpg', 'size' => 156500, 'width' => 3000, 'height' => 2000]);
                        $dbTable->insert(['id' => 9, 'caption' => '', 'created' => '2017-02-01 12:24:54', 'path' => 'content/uploads/2016/10/', 'filename' => 'logo-color', 'extension' => 'png', 'size' => 20000, 'width' => 610, 'height' => 610]);
                    }
                } catch(\PDOException $e){
                    throw new \Exception(
                        'Unable to create database schema [' . $ddl->getTable() . ']: ' . $e->getMessage(),
                        self::INIT_FAILED
                    );
                }
            }
            $adapter->query('DROP VIEW IF EXISTS ' . TableGateway::$prefix . 'view_posts;', Adapter::QUERY_MODE_EXECUTE);
            $adapter->query(
                    str_replace('__', TableGateway::$prefix, "CREATE VIEW __view_posts AS
                        SELECT  
                        a.id, a.slug, a.created, a.pub_date, c.slug as author, a.title, a.content, 
                        a.meta_title, a.meta_description, a.status, a.page, a.featured, a.sticky, 
                        CONCAT_WS('.', CONCAT(b.path, b.filename), b.extension) as image
                        FROM 
                        `__posts` a
                        LEFT JOIN 
                        `__uploads` b
                        ON 
                        a.image = b.id
                        INNER JOIN
                        `__users` c
                        ON
                        a.author = c.id
                        "), 
                    Adapter::QUERY_MODE_EXECUTE
                );
    }

    /**
    * Begins a transaction
    *
    * @return bool
    *
    **/
    public static function beginTransaction(){
        return self::$database->beginTransaction();
    }

    /**
    * Rollback a transaction
    *
    * @return bool
    *
    **/ 
    public static function rollBack() {
        return self::$database->rollBack();
    }

    /**
    * Commit a transaction
    *
    * @return bool
    *
    **/
    public static function commit(){
        return self::$database->commit();
    }

    /**
    * Truncate a table
    *
    * @param String $table
    * @return bool
    *
    **/
    public static function truncate($table){
        return self::$database->exec('TRUNCATE ' . $table);
    }

    /**
    *
    **/

    public static function updateDatabase(){
        self::$logger->info(' Begin database update ');
        self::$logger->info(' creating backup ');
        \Leafpub\Backup::create();
        $dbScheme = \Leafpub\Models\Setting::getOne('schemeVersion') ?: 0;

        for ($i = $dbScheme; $i < LEAFPUB_SCHEME_VERSION; $i++){
            $method = 'updateToVersion' . ($i + 1);
            self::$method();
        }
    }

    /**
    * Create __plugins table
    *
    **/
    protected static function updateToVersion1(){
        self::$logger->info(' start updateToVersion1 ');
        
        $adapter = GlobalAdapterFeature::getStaticAdapter();
        $sql = new \Zend\Db\Sql\Sql($adapter);
        $isMySQL = (strtolower($adapter->getDriver()->getDatabasePlatformName()) == 'mysql');
        
        $table = new \Leafpub\Models\Ddl\Plugin();
        $tableSQL = $sql->getSqlStringForSqlObject($table);
        if ($isMySQL){
            $tableSQL .= ' ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;';
        }
        
        $adapter->query($tableSQL, Adapter::QUERY_MODE_EXECUTE);
        
        self::$logger->info(' end updateToVersion1 ');
    }

    protected static function updateToVersion2(){
        self::$logger->info(' start updateToVersion2 ');
        $adapter = GlobalAdapterFeature::getStaticAdapter();
        $sql = new \Zend\Db\Sql\Sql($adapter);
        $isMySQL = (strtolower($adapter->getDriver()->getDatabasePlatformName()) == 'mysql');
        try {
            self::$logger->info('updating table __uploads');
            $atu = new \Zend\Db\Sql\Ddl\AlterTable(TableGateway::$prefix . 'uploads');
            $atu->addColumn(new \Zend\Db\Sql\Ddl\Column\Varchar('caption', 91, true));
            $atu->addConstraint(new \Zend\Db\Sql\Ddl\Constraint\UniqueKey('filename'));
            $adapter->query($sql->getSqlStringForSqlObject($atu), Adapter::QUERY_MODE_EXECUTE);
            /*
            $atu = new \Zend\Db\Sql\Ddl\AlterTable(TableGateway::$prefix . 'uploads');
            $atu->dropConstraint('path');
            */
            $adapter->query('ALTER TABLE ' . TableGateway::$prefix . 'uploads DROP INDEX path', Adapter::QUERY_MODE_EXECUTE);

            self::$logger->info('updating table __tags');
            $att = new \Zend\Db\Sql\Ddl\AlterTable(TableGateway::$prefix . 'tags');
            $att->addColumn(new \Leafpub\Models\Ddl\Column\Enum('type', ["'post'", "'upload'"], false, 'post'));
            $adapter->query($sql->getSqlStringForSqlObject($att), Adapter::QUERY_MODE_EXECUTE);

            self::$logger->info('creating table __upload_tags');
            $ut = new \Leafpub\Models\Ddl\UploadTags();
            $tableSQL = $sql->getSqlStringForSqlObject($ut);
            if ($isMySQL){
                $tableSQL .= ' ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;';
            }

            $adapter->query($tableSQL, Adapter::QUERY_MODE_EXECUTE);

            self::$logger->info('creating table __post_uploads');
            $pu = new \Leafpub\Models\Ddl\PostUploads();
            $tableSQL = $sql->getSqlStringForSqlObject($pu);
            if ($isMySQL){
                $tableSQL .= ' ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;';
            }

            $adapter->query($tableSQL, Adapter::QUERY_MODE_EXECUTE);
        } catch (\Exception $e){
            self::$logger->error($e->getMessage());
        }

        self::$logger->info(' Generating thumbnails and edit path ');

        $uploads = $adapter->query('SELECT * FROM ' . TableGateway::$prefix . 'uploads;', Adapter::QUERY_MODE_EXECUTE)->toArray();

        foreach($uploads as $upload){
            if (strpos($upload['filename'], $upload['extension']) > 0){
                $extLength = strlen($upload['extension']) + 1;

                $newFilename = substr($upload['filename'], 0, strlen($upload['filename']) - $extLength);
                $newPath = substr($upload['path'], 0, strlen($upload['path']) - strlen($upload['filename']));

                self::$logger->info("new Filename: $newFilename\nnew Path: $newPath");

                try {
                    $sSQL = 'UPDATE ' . TableGateway::$prefix . 'uploads SET path = \'' . $newPath . '\', filename = \'' . $newFilename . '\' WHERE id = ' . $upload['id'] . ';';
                    $adapter->query($sSQL, Adapter::QUERY_MODE_EXECUTE);
                    
                    if(!self::makeDir(self::path($newPath.'thumbnails'))) {
                        throw new \Exception(
                            'Unable to create directory: ' . $newPath.'thumbnails',
                            self::UNABLE_TO_CREATE_DIRECTORY
                        );
                    }
                    $relativeThumb = "$newPath"."thumbnails/" . $upload['filename'];
                    $thumbPath = self::path($relativeThumb);
                    $imgPath = self::path($upload['path']);

                    if (is_file($imgPath)){
                        Upload::generateThumbnail($imgPath, $thumbPath);
                    }
                    self::$logger->info("Updated $newFilename and generated a thumbnail");
                } catch (\PDOException $e){
                    self::$logger->error($e->getMessage());
                }
            }
        }

        self::$logger->info(' creating view __view_posts ');
        try {
            $adapter->query(
                        str_replace('__', TableGateway::$prefix, "CREATE VIEW __view_posts AS
                            SELECT  
                            a.id, a.slug, a.created, a.pub_date, c.slug as author, a.title, a.content, 
                            a.meta_title, a.meta_description, a.status, a.page, a.featured, a.sticky, 
                            CONCAT_WS('.', CONCAT(b.path, b.filename), b.extension) as image
                            FROM 
                            `__posts` a
                            LEFT JOIN 
                            `__uploads` b
                            ON 
                            a.image = b.id
                            INNER JOIN
                            `__users` c
                            ON
                            a.author = c.id
                            "), 
                        Adapter::QUERY_MODE_EXECUTE
                    );

            self::$logger->info(' end updateToVersion2 ');
        } catch (\Exception $e){
            self::$logger->error($e->getMessage());
        }
    }

    protected static function updateToVersion3(){
        self::$logger->info(' start updateToVersion3 ');

        $adapter = GlobalAdapterFeature::getStaticAdapter();
        $sql = new \Zend\Db\Sql\Sql($adapter);
        $isMySQL = (strtolower($adapter->getDriver()->getDatabasePlatformName()) == 'mysql');
        try {
            $table = new \Leafpub\Models\Ddl\PostMeta();
            $tableSQL = $sql->getSqlStringForSqlObject($table);
            if ($isMySQL){
                $tableSQL .= ' ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;';
            }
            
            $adapter->query($tableSQL, Adapter::QUERY_MODE_EXECUTE);

            self::$logger->info(' end updateToVersion3 ');
        }catch(\Exception $e){
            self::$logger->error($e->getMessage());
        }
    }
}