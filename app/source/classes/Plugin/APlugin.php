<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Plugin;

use Leafpub\Leafpub;
use Leafpub\Models\Setting;
use Leafpub\Renderer;

abstract class APlugin
{
    /**
     * @var string
     */
    public string $_url;
    public const NAME = '';
    /**
     * @var string
     */
    private string $_name = '';
    /**
     * @var string
     */
    private string $_version = '';
    /**
     * @var string
     */
    private string $_author = '';
    /**
     * @var string
     */
    private string $_license = '';
    /**
     * @var string
     */
    private string $_requires = '';
    /**
     * @var string
     */
    private string $_image = '';
    /**
     * @var bool
     */
    private bool $_isAdminPlugin = false;
    /**
     * @var bool
     */
    private bool $_isWidget = false;
    /**
     * @var string
     */
    private string $_description = '';

    /**
     * @var null|\Slim\App
     */
    private ?\Slim\App $_app = null;

    public function __construct(\Slim\App $app)
    {
        $this->_app = $app;
        $this->setOptions();
    }

    // If plugin is a middleware, you need to overwrite the __invoke function
    public function __invoke($req, $res, $next)
    {
        return $next($req, $res);
    }

    /**
     * @return string Plugin Name + Plugin Version
     */
    public function __toString()
    {
        return $this->_name . '@' . $this->_version;
    }

    /**
     * Will be triggered after plugin activation
     * You could create database tables for example
     */
    public static function afterActivation()
    {
    }

    /**
     * Will be triggered before plugin gets removed
     *
     **/
    public static function afterDeactivation()
    {
    }

    /**
     * Returns the plugin name
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * Returns the plugin version
     */
    public function getVersion(): string
    {
        return $this->_version;
    }

    /**
     * Returns the author's name
     */
    public function getAuthor(): string
    {
        return $this->_author;
    }

    /**
     * Returns the license
     */
    public function getLicense(): string
    {
        return $this->_license;
    }

    public function getDescription(): string
    {
        return $this->_description;
    }

    public function getImage(): string
    {
        if ($this->_image === '') {
            return 'source/assets/img/app-icon.png';
        }

        return $this->_image;
    }

    public function isWidget(): bool
    {
        return $this->_isWidget;
    }

    /**
     * Returns the plugin address
     */
    public function getPluginAddress(): string
    {
        return $this->_url;
    }

    /**
     * Returns the required Leafpub version
     */
    public function getRequiredLeafpubVersion(): string
    {
        return $this->_requires;
    }

    public function url($path = null): string
    {
        $safeName = Leafpub::slug($this->_name);
        if ($this->_isAdminPlugin) {
            $admin = Setting::getOne('frag_admin');

            return Leafpub::url("/$admin/$safeName/$path");
        }

        return Leafpub::url("/$safeName/$path");
    }

    public static function getSetting($name)
    {
        return Setting::getOne($name);
    }

    public static function setSetting($name, $option)
    {
        $ret = Setting::getOne($name);

        if ($ret === null) {
            return Setting::create(['name' => $name, 'value' => $option]);
        }

        return Setting::edit(['name' => $name, 'value' => $option]);
    }

    /**
     * Renders an page
     *
     * @param string $template
     * @param null   $data
     *
     * @return mixed
     *
     **/
    public static function render($template, $data = null, $dir = null): string
    {
        return Renderer::render([
            'template' => Leafpub::path('content/plugins/' . $dir . "/templates/$template.hbs"),
            'data' => $data,
            'special_vars' => [],
            'helpers' => ['admin', 'url', 'utility'],
        ]);
    }

    /**
     * Reads the plugin.json and autoconfigure the plugin
     */
    protected function setOptions(): void
    {
        // Generate path from the class and read the plugin.json
        $dir = array_slice(explode('\\', get_class($this)), 0, -1)[2];
        $plugin_json = Leafpub::path('content/plugins/' . $dir . '/plugin.json');
        $json = json_decode(file_get_contents($plugin_json), true);

        // Fill info vars
        $this->_name = $json['name'];
        $this->_description = $json['description'];
        $this->_author = $json['author'];
        $this->_version = $json['version'];
        $this->_license = $json['license'];
        $this->_requires = $json['requires'];
        $this->_image = $json['image'];
        $this->_isAdminPlugin = $json['isAdminPlugin'];
        $this->_isWidget = $json['isWidget'];

        if ($json['isMiddleware'] == true) {
            $this->_app->add($this);
        } elseif (isset($json['routes'])) {
            $this->addRoutes($json['routes']);
        }
    }

    /**
     * Adds routes to Leafpub
     */
    private function addRoutes(array $routes): void
    {
        $safeName = Leafpub::slug($this->_name);
        if ($this->_isAdminPlugin) {
            $admin = Setting::getOne('frag_admin');
            $this->_app->group("/$admin/$safeName", function () use ($routes) {
                foreach ($routes as $route) {
                    $method = $route['method'];
                    $uri = $route['uri'];
                    $cb = $route['cb'];
                    $this->{$method}($uri, $cb);
                }
            })->add('Leafpub\Middleware:requireAuth');
        } else {
            $this->_app->group("/$safeName", function () use ($routes) {
                foreach ($routes as $route) {
                    $method = $route['method'];
                    $uri = $route['uri'];
                    $cb = $route['cb'];
                    $this->{$method}($uri, $cb);
                }
            });
        }
    }
}
