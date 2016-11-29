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
* Theme
*
* methods for working with themes
* @package Leafpub
*
**/
class Theme extends Leafpub {

    /**
    * Returns an array of all available themes
    *
    * @return array
    *
    **/
    public static function getAll() {
        $themes = [];

        $iterator = new DirectoryIterator(self::path('content/themes'));
        foreach($iterator as $dir) {
            if(!$dir->isDot() && $dir->isDir()) {
                // Attempt to read and decode theme.json
                $theme_json = $dir->getPathname() . '/theme.json';
                $json = json_decode(file_get_contents($theme_json), true);
                if(!$json) continue;
                // Add it
                $themes[] = array_merge($json, ['dir' => $dir->getFilename()]);
            }
        }

        return $themes;
    }

    /**
    * Returns the path to the current theme, optionally concatenating a path
    *
    * @return String
    *
    **/
    public static function getPath() {
        $paths = func_get_args();
        $base_path = 'content/themes/' . Setting::get('theme');

        return self::path($base_path, implode('/', $paths));
    }

}