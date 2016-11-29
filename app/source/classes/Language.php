<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

/**
* Language
*
* methods for working with language packs
* @package Leafpub
*
**/
class Language extends Leafpub {

    /**
    * Loads the language pack from file and stores it in a static variable for quick access
    *
    * @param String $language
    * @return void
    * @throws \Exception
    *
    **/
    protected static function load($language = 'en-us') {
        // Load the en-us pack first since it's the default. We do this so missing terms from other
        // language packs will fallback to the English equivalent.
        $default = self::path('source/languages/en-us.php');
        if(file_exists($default)) {
            self::$language = (array) include $default;
        } else {
            throw new \Exception('Language pack not found: ' . basename($default) . '.php');
        }

        // Load the specified language pack
        if($language !== 'en-us') {
            $file = self::path('source/languages/' . $language . '.php');
            if(file_exists($file)) {
                self::$language = array_merge(self::$language, (array) include $file);
            } else {
                throw new \Exception('Language pack not found: ' . basename($language) . '.php');
            }
        }
    }

    /**
    * Gets an array of all available language packs
    *
    * @return array
    *
    **/
    public static function getAll() {
        $languages = [];

        $iterator = new \DirectoryIterator(self::path('source/languages'));
        foreach($iterator as $file) {
            if($file->isFile() && $file->getExtension() === 'php') {
                $lang = include $file->getPathname();
                if(isset($lang['language_code']) && isset($lang['language_name'])) {
                    $languages[] = [
                        'name' => $lang['language_name'],
                        'code' => $lang['language_code']
                    ];
                }
            }
        }

        return $languages;
    }

    /**
    * Gets a term and updates placeholders, if needed
    *
    * @param String $term
    * @param null $placeholders
    * @return String
    *
    **/
    public static function term($term, $placeholders = null) {
        // Get requested term. If non-existent, wrap in brackets so we can identify missing terms
        $term = isset(self::$language[$term]) ? self::$language[$term] : '[' . $term . ']';

        // Fill in placeholders
        if(is_array($placeholders)) {
            foreach($placeholders as $key => $value) {
                $term = str_replace('{' . $key . '}', $value, $term);
            }
        }

        return $term;
    }

}