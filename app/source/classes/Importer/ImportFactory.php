<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Importer;

/**
 * ImportFactory
 *
 * Generates an instance of Leafpub\Importer\Dropin\*
 *
 **/
class ImportFactory
{
    /**
     * Factory function for import functionality
     *
     * @param string $system
     * @param string $file
     *
     * @throws \Exception
     *
     *
     **/
    public static function factory($system, $file): \Leafpub\Importer\AbstractImporter
    {
        $class_to_load = __NAMESPACE__ . '\Dropins\\' . ucfirst($system);
        if (class_exists($class_to_load)) {
            return new $class_to_load($file);
        }
        throw new \Exception('Dropin ' . $system . ' wasn\'t found!' . $class_to_load);
    }
}
