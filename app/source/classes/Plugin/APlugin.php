<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Plugin;

use Leafpub\Leafpub,
    Leafpub\Setting;

abstract class APlugin {
    private $_name = '';
    private $_version = '';
    private $_author = '';
    private $_license = '';
    private $_link = '';
    private $_requires = '';
    private $_image = '';
    private $_isAdminPlugin = false;

    private $_app = null;

    public function __construct(\Slim\App $app){
        $this->_app = $app;
        //$this->setOptions();
    }

    /**
    * Reads the plugin.json and autoconfigure the plugin
    *
    * @return void
    *
    */
    protected function setOptions($dir){
        // Read the plugin.json
        $plugin_json = $dir . '/plugin.json';
        $json = json_decode(file_get_contents($plugin_json), true);
        
        // Fill info vars
        $this->_name = $json['name'];
        $this->_author = $json['author'];
        $this->_version = $json['version'];
        $this->_license = $json['license'];
        $this->_requires = $json['requires'];
        $this->_link = $json['link'];
        $this->_image = $json['image'];
        $this->_isAdminPlugin = $json['isAdminPlugin'];

        if ($json['isMiddleware'] == true){
            $this->_app->add($this);
        } else {
            // Add routes to app, if present
            // and only, if this plugin isn't a middleware
            if (isset($json['routes'])){
                $this->addRoutes($json['routes']);
            }
        }
    }

    /**
    * Adds routes to Leafpub
    *
    * @param array $routes
    * @return void
    *
    */
    private function addRoutes(array $routes){
        $safeName = Leafpub::slug($this->_name);
        if ($this->_isAdminPlugin){
            $admin = Setting::get('frag_admin');
            $this->_app->group("/$admin/" . $safeName , function() use($routes){
                foreach($routes as $route){
                    $method = $route["method"];
                    $uri = $route["uri"];
                    $cb = $route["cb"];
                    $this->{$method}($uri, $cb);
                }
            })->add('Leafpub\Middleware:requireAuth');
        } else {
            $this->_app->group('/' . $safeName, function() use($routes){
                foreach($routes as $route){
                    $method = $route["method"];
                    $uri = $route["uri"];
                    $cb = $route["cb"];
                    $this->{$method}($uri, $cb);
                }
            });
        }
    }

    // If plugin is a middleware, you need to overwrite the __invoke function
    public function __invoke($req, $res, $next){
        return $next($req, $res);
    }

    /**
    *
    * @return String Plugin Name + Plugin Version
    *
    */
    public function __toString(){
        return $this->_name . '@' . $this->_version;
    }

    /**
    * Returns the plugin name
    *
    * @return String
    *
    */
    public function getName(){
        return $this->_name;
    }

    /**
    * Returns the plugin version
    *
    * @return String
    *
    */
    public function getVersion(){
        return $this->_version;
    }

    /**
    * Returns the author's name
    *
    * @return String
    *
    */
    public function getAuthor(){
        return $this->_author;
    }

    /**
    * Returns the author's name
    *
    * @return String
    *
    */
    public function getLicense(){
        return $this->_license;
    }

    /**
    * Returns the author's name
    *
    * @return String
    *
    */
    public function getPluginAddress(){
        return $this->_url;
    }

    /**
    * Returns the author's name
    *
    * @return String
    *
    */
    public function getRequiredLeafpubVersion(){
        return $this->_requires;
    }

}