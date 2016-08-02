<?php
//
// Postleaf\Cache: methods for working with cache files
//
namespace Postleaf;

class Cache extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Constants
    ////////////////////////////////////////////////////////////////////////////////////////////////

    const
        UNABLE_TO_CREATE_DIRECTORY = 1,
        UNABLE_TO_DELETE_FILE = 2,
        UNABLE_TO_READ_FILE = 3,
        UNABLE_TO_WRITE_FILE = 4;

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Deletes a cache file
    public static function delete($filename = null) {
        // Determine full path
        $filename = self::path('content/cache', $filename);

        // Delete it if it exists
        if(file_exists($filename)) {
            if(!unlink($filename)) {
                throw new \Exception(
                    'Unable to delete cache file: ' . $filename,
                    self::UNABLE_TO_DELETE_FILE
                );
            }
        }

        return true;
    }

    // Deletes all files in the cache directory. If $prefix is set, only files starting with
    // that string will be deleted.
    public static function flush($prefix = null) {
        // There's nothing to do if the directory doesn't exist
        if(!file_exists(self::path('content/cache'))) return true;

        // Loop through the cache directory and flush matching files
        $iterator = new \DirectoryIterator(self::path('content/cache'));
        foreach($iterator as $file) {
            if($file->getFilename() === '.gitignore') continue;
            if($file->isFile()) {
                // If a prefix is specified and the file doesn't have it, don't delete it
                if(
                    $prefix &&
                    mb_substr($file->getFilename(), 0, mb_strlen($prefix)) !== $prefix
                ) {
                    continue;
                }

                // Delete the file
                if(!unlink($file->getPathname())) {
                    throw new \Exception(
                        'Unable to delete cache file: ' . $file->getPathname(),
                        self::UNABLE_TO_DELETE_FILE
                    );
                }
            }
        }

        return true;
    }

    // Reads a cache file. If no cache file exists, false is returned.
    public static function get($filename) {
        // Read the cache file
        $filename = self::path('content/cache', $filename);
        if(!file_exists($filename)) return false;

        $data = file_get_contents($filename);
        if($data === false) {
            throw new \Exception(
                'Unable to read cache file: ' . $filename,
                self::UNABLE_TO_READ_FILE
            );
        }

        return $data;
    }

    // Writes a cache file
    public static function put($filename, $data) {
        // Create the cache directory if it doesn't exist
        if(!self::makeDir(self::path('content/cache'))) {
            throw new \Exception(
                'Unable to create cache directory: ' . self::path('content/cache'),
                self::UNABLE_TO_CREATE_DIRECTORY
            );
        }

        // Write the cache file
        $filename = self::path('content/cache', $filename);
        if(file_put_contents($filename, $data) === false) {
            throw new \Exception(
                'Unable to write cache file: ' . $filename,
                self::UNABLE_TO_WRITE_FILE
            );
        }

        return true;
    }

}