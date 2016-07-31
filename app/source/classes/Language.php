<?php
//
// Postleaf\Language: methods for working with language packs
//
namespace Postleaf;

class Language extends Postleaf {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Protected methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Loads the language pack from file and stores it in a static variable for quick access
    protected static function load($language = 'en-us') {
        // Get path to requested language pack
        $file = self::path('source/languages/' . $language . '.php');

        // Does it exist?
        if(!file_exists($file)) {
            throw new \Exception('Language pack not found: ' . $language . '.php');
        }

        // Load it
        self::$language = (array) include $file;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Public methods
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Gets an array of all available language packs
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

    // Gets a term and updates placeholders, if needed
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