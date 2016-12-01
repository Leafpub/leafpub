<?php
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
* @package Leafpub\Importer
*
**/
class ImportFactory {
    /**
    * Factory function for import functionality
    *
    * @param String $system
    * @param String $file
    * @return Leafpub\Importer\AbstractImporter
    * @throws \Exception
    *
    **/
    public static function factory($system, $file){
        $class_to_load = __NAMESPACE__ . '\Dropins\\' . ucfirst($system);
        if (class_exists($class_to_load)){
            return new $class_to_load($file);
        } else {
            throw new \Exception('Dropin ' . $system . ' wasn\'t found!' . $class_to_load);
        }
    }
}
?>