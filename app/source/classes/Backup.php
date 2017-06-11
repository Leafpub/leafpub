<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;
use Leafpub\Models\Setting;

/**
* Backup
*
* methods for working with backups
* @package Leafpub
*
**/
class Backup extends Leafpub {

    /**
    * Constants
    **/
    const
        NOT_FOUND = 1,
        REQUIRED_FILE_IS_MISSING = 2,
        UNABLE_TO_BACKUP_DATABASE = 3,
        UNABLE_TO_CREATE_ARCHIVE = 4,
        UNABLE_TO_CREATE_DIRECTORY = 5,
        UNABLE_TO_DELETE_DIRECTORY = 6,
        UNABLE_TO_EXTRACT_ARCHIVE = 7,
        UNABLE_TO_MOVE_FILE = 8,
        UNABLE_TO_RESTORE_DATABASE = 9;

    /**
    * Fetch a database table and convert it to JSON
    *
    * @param String $table
    * @return mixed
    *
    **/
    private static function dbToJson($table) {
        try {
            $x = '\\Leafpub\\Models\\Tables\\' . ucfirst($table);
            $dbTable = new $x();
            $result = $dbTable->select()->toArray();
        } catch(\PDOException $e) {
            return false;
        }

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
    * Truncates a database table and restores values from JSON
    *
    * @param String $table
    * @param String $json
    * @return bool
    *
    **/
    private static function jsonToDb($table, $json) {
        // Decode it
        $rows = json_decode($json, true);
        if($rows === null) return false;

        $x = '\\Leafpub\\Models\\Tables\\' . $table;
        $dbTable = new $x();
        
        // Restore the table
        try {
            // Truncate the table
           $dbTable->truncate();

            // Insert each row
            foreach($rows as $row) {
                $dbTable->insert($row);
            }
        } catch(\PDOException $e) {
            return false;
        }

        return true;
    }

    /**
    * Creates a backup file
    *
    * @return array
    * @throws \Exception
    **/
    public static function create() {
        $tmp_dir = self::path('backups/create-' . uniqid());

        // Create /backups and /backups/create-{id}
        if(!self::makeDir(self::path('backups')) || !self::makeDir($tmp_dir)) {
            throw new \Exception(
                'Unable to create backup directory: ' . self::path('backups'),
                self::UNABLE_TO_CREATE_DIRECTORY
            );
        }

        // Generate JSON files for each table in /backups/create-{id}
        foreach(self::getTableNames() as $table) {
            $json = self::dbToJson($table);
            $written = file_put_contents("$tmp_dir/$table.json", $json);
            if(!$json || !$written) {
                            var_dump($json);
                throw new \Exception(
                    'Unable to backup database table: ' . $table,
                    self::UNABLE_TO_BACKUP_DATABASE
                );
            }
        }

        // Generate pathname. Ex: backups/a-leafpub-blog.2016-06-15.tar
        $filename = str_replace('.', '', self::safeFilename(Setting::getOne('title')));
        $pathname = self::path(
            'backups/' .
            $filename .
            '.' . date('Y-m-d') . '.tar'
        );
        // Loop until we find a unique filename
        $i = 1;
        while(file_exists($pathname)) {
            $pathname = self::path(
                'backups/' .
                $filename .
                '.' . date('Y-m-d') . '_' . $i++ . '.tar'
            );
        }

        // Build an iterator to include /content
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                self::path('content'),
                \RecursiveDirectoryIterator::SKIP_DOTS
            )
        );
        // Ignore certain files and directories
        $filterIterator = new \CallbackFilterIterator($iterator, function($file) {
            // Ignore these files
            $ignore_files = [
                '.DS_Store'
            ];
            foreach($ignore_files as $f) {
                if($file->getFilename() === $f) return false;
            }

            // Ignore these folders
            $ignore_folders = [
                self::path('content/cache')
            ];
            foreach($ignore_folders as $f) {
                if(mb_substr($file->getPathname(), 0, mb_strlen($f)) === $f) return false;
            }

            return true;
        });

        // Create the tar
        try {
            // Set a longer execution limit since this could take some time
            $time_limit = ini_get('max_execution_time');
            ini_set('max_execution_time', 300);

            // Tar the content directory
            $phar = new \PharData($pathname);
            $phar->buildFromIterator($filterIterator, self::path());

            // Add JSON data files to it
            foreach(self::getTableNames() as $table) {
                $phar->addFile("$tmp_dir/$table.json", "data/$table.json");
            }

            // Restore original execution limit
            ini_set('max_execution_time', $time_limit);
        } catch(\Exception $e) {
            // Cleanup the partial tar if it was created
            if(file_exists($pathname)) unlink($pathname);
            // Log error msg to logfile
            Leafpub::getLogger()->error($e->getMessage());
            throw new \Exception(
                'Unable to archive backup files: ' . $e->getMessage(),
                self::UNABLE_TO_CREATE_ARCHIVE
            );
        }

        // Cleanup /backups/create-{id}
        self::removeDir($tmp_dir);

        return [
            'pathname' => $pathname,
            'filename' => basename($pathname),
            'date' => date('Y-m-d H:i:s', filemtime($pathname)),
            'size' => filesize($pathname)
        ];
    }

    /**
    * Deletes the specified backup file
    *
    * @param String $filename
    * @return bool
    *
    **/
    public static function delete($filename) {
        $pathname = self::path('backups', $filename);
        return file_exists($pathname) ? unlink($pathname) : false;
    }

    /**
    * Gets a backup file
    *
    * @param String $filename
    * @return mixed
    *
    **/
    public static function get($filename) {
        $pathname = self::path('backups', $filename);
        if(!file_exists($pathname)) return false;

        return [
            'pathname' => $pathname,
            'filename' => basename($pathname),
            'date' => date('Y-m-d H:i:s', filemtime($pathname)),
            'size' => filesize($pathname)
        ];
    }

    /**
    * Gets an array of all available backups
    *
    * @return array
    *
    **/
    public static function getAll() {
        if(!file_exists(self::path('backups'))) return [];

        $backups = [];
        $iterator = new \DirectoryIterator(self::path('backups'));
        foreach($iterator as $file) {
            if($file->isFile() && $file->getExtension() === 'tar') {
                $backups[] = [
                    'pathname' => $file->getPathname(),
                    'filename' => $file->getFilename(),
                    'date' => date('Y-m-d H:i:s', $file->getMTime()),
                    'size' => $file->getSize()
                ];
            }
        }

        // Sort by date, newest first
        usort($backups, function($a, $b) {
            return $a['date'] < $b['date'];
        });

        return $backups;
    }

    /**
    * Restores content and data to the specified backup file
    *
    * @param String $file
    * @return void
    * @throws \Exception
    *
    **/
    public static function restore($file) {
        $content_dir = self::path('content');
        $tmp_dir = self::path('backups/restore-' . uniqid());

        // Get backup file
        $backup = self::get($file);
        if(!$backup) {
            throw new \Exception(
                'Backup not found: ' . $file,
                self::NOT_FOUND
            );
        }

        // Create temp directory: /backups/restore-{id}
        if(!self::makeDir($tmp_dir)) {
            throw new \Exception(
                'Unable to create new content directory: ' . $tmp_dir,
                self::UNABLE_TO_CREATE_DIRECTORY
            );
        }

        // Untar backup file to temp directory
        try {
            // Set a longer execution limit since this could take some time
            $time_limit = ini_get('max_execution_time');
            ini_set('max_execution_time', 300);

            // Untar the archive
            $phar = new \PharData($backup['pathname']);
            $phar->extractTo($tmp_dir, null, true);

            // Restore original execution limit
            ini_set('max_execution_time', $time_limit);
        } catch(\Exception $e) {
            // Cleanup the temp directory
            self::removeDir($tmp_dir);

            throw new \Exception(
                'Unable to extract backup archive: ' . $e->getMessage(),
                self::UNABLE_TO_EXTRACT_ARCHIVE
            );
        }

        // Make sure all data files exist
        foreach(self::getTableNames() as $table) {
            $file = "$tmp_dir/data/$table.json";
            if(!file_exists($file)) {
                // Cleanup the temp directory
                self::removeDir($tmp_dir);

                throw new \Exception(
                    "Required data file is missing from backup: data/$table.json",
                    self::REQUIRED_FILE_IS_MISSING
                );
            }
        }

        // Restore each table
        foreach(self::getTableNames() as $table) {
            $file = "$tmp_dir/data/$table.json";
            if(!self::jsonToDb($table, file_get_contents($file))) {
                // Cleanup temp dir
                self::removeDir($tmp_dir);

                throw new \Exception(
                    'Unable to restore database table: ' . $table,
                    self::UNABLE_TO_RESTORE_DATABASE
                );
            }
        }

        // Delete /content
        if(!self::removeDir($content_dir)) {
            throw new \Exception(
                'Unable to replace content folder: ' . $content_dir,
                self::UNABLE_TO_DELETE_DIRECTORY
            );
        }

        // Move /backups/restore-{id}/content to /content
        if(!rename("$tmp_dir/content", $content_dir)) {
            throw new \Exception(
                "Unable to move $tmp_dir/content to $content_dir",
                self::UNABLE_TO_MOVE_FILE
            );
        }

        // Clean up temp directory
        self::removeDir($tmp_dir);
    }

}