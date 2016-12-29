<?php
    namespace Leafpub;

    $plugins = Plugin::getActivatedPlugins();

    foreach($plugins as $plugin){
        $ns = ucfirst($plugin['dir']);
        $class = 'Leafpub\\Plugins\\' . $ns . '\\Plugin';
        $plugins[] = new $class($app);
        /*
        $container[$class] = function($app){
            return new $class($app);
        };
        */
    }
    Plugin::$plugins = $plugins;

