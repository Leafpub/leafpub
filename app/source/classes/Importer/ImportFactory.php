<?php
namespace Postleaf\Importer;

class ImportFactory {
    public static function factory($system, $file){
        $class_to_load = __NAMESPACE__ . '\\' . ucfirst($system);
        if (class_exists($class_to_load)){
            return new $class_to_load($file);
        }
    }
}
?>