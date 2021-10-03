<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use DirectoryIterator;
use Leafpub\Models\Setting;

/**
 * Theme
 *
 * methods for working with themes
 *
 **/
class Theme extends Leafpub
{
    public const THEME_STD_ERROR = 'error.hbs';
    public const THEME_STD_MAINTENANCE = 'source/templates/maintenance.hbs';

    /**
     * @var null
     */
    private static $_themeOptions;

    /**
     * Returns an array of all available themes
     *
     *
     **/
    public static function getAll(): array
    {
        $themes = [];

        $iterator = new DirectoryIterator(self::path('content/themes'));
        foreach ($iterator as $dir) {
            if (!$dir->isDot() && $dir->isDir()) {
                // Attempt to read and decode theme.json
                $theme_json = $dir->getPathname() . '/theme.json';
                $json = json_decode(file_get_contents($theme_json), true);
                if (!$json) {
                    continue;
                }
                // Add it
                $themes[] = array_merge($json, ['dir' => $dir->getFilename()]);
            }
        }

        return $themes;
    }

    /**
     * Returns the path to the current theme, optionally concatenating a path
     *
     *
     **/
    public static function getPath(...$paths): string
    {
        $base_path = 'content/themes/' . Setting::getOne('theme');

        return self::path($base_path, implode('/', $paths));
    }

    public static function getErrorTemplate($code): string
    {
        if (!self::$_themeOptions) {
            self::$_themeOptions = json_decode(
                                        file_get_contents(
                                            self::path('content/themes/' . Setting::getOne('theme') . '/theme.json'), true
                                        )
                                    );
        }
        $file = self::getPath(self::$_themeOptions->error_templates->{$code});
        if (!is_file($file)) {
            $file = $code == '503' ? self::path(self::THEME_STD_MAINTENANCE) : self::getPath(self::THEME_STD_ERROR);
        }

        return $file;
    }
}
