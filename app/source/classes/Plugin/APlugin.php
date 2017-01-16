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
    Leafpub\Setting,
    Leafpub\Renderer;

abstract class APlugin {
    const NAME = '';
    private $_name = '';
    private $_version = '';
    private $_author = '';
    private $_license = '';
    private $_link = '';
    private $_requires = '';
    private $_image = '';
    private $_isAdminPlugin = false;
    private $_dir = '';

    private $_app = null;

    public function __construct(\Slim\App $app){
        $this->_app = $app;
        $this->setOptions();
    }

    /**
    * Reads the plugin.json and autoconfigure the plugin
    *
    * @return void
    *
    */
    protected function setOptions(){
        // Generate path from the class and read the plugin.json
        $dir = array_slice(explode('\\', get_class($this)), 0, -1)[2];
        $plugin_json = Leafpub::path('content/plugins/' . $dir . '/plugin.json');
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
        $this->_dir = $dir;

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
            $this->_app->group("/$admin/$safeName" , function() use($routes){
                foreach($routes as $route){
                    $method = $route["method"];
                    $uri = $route["uri"];
                    $cb = $route["cb"];
                    $this->{$method}($uri, $cb);
                }
            })->add('Leafpub\Middleware:requireAuth');
        } else {
            $this->_app->group("/$safeName", function() use($routes){
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
    * Will be triggered after plugin activation
    * You could create database tables for example
    *
    */
    public static function afterActivation(){}

    /**
    *
    * Will be triggered before plugin gets removed
    *
    **/
    public static function afterDeactivation(){}

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
    * Returns the license
    *
    * @return String
    *
    */
    public function getLicense(){
        return $this->_license;
    }

    /**
    * Returns the plugin address
    *
    * @return String
    *
    */
    public function getPluginAddress(){
        return $this->_url;
    }

    /**
    * Returns the required Leafpub version
    *
    * @return String
    *
    */
    public function getRequiredLeafpubVersion(){
        return $this->_requires;
    }

    public function url($path = null){
        $safeName = Leafpub::slug($this->_name);
        if ($this->_isAdminPlugin){
            $admin = Setting::get('frag_admin');
            return Leafpub::url("/$admin/$safeName/$path");
        } else {
            return Leafpub::url("/$safeName/$path");
        }
    }

    public static function getSetting($name){
        return Setting::get($name);
    }

    public static function setSetting($name, $option){
        $ret = Setting::get($name);

        if ($ret === ''){
            return Setting::add($name, $option);
        } else {
            return Setting::update($name, $option);
        }
    }

     /**
    * Renders an page
    *
    * @param String $template
    * @param null $data
    * @return mixed
    *
    **/
    public static function render($template, $data = null, $dir = null) {
        return Renderer::render([
            'template' => Leafpub::path("content/plugins/" . $dir . "/templates/$template.hbs"),
            'data' => $data,
            'special_vars' => [],
            'helpers' => ['admin', 'url', 'utility']
        ]);
    }

}