<?php
namespace Leafpub;

define('LEAFPUB_VERSION', '{{version}}');
define('LEAFPUB_SCHEME_VERSION', 2);
define('LEAFPUB_DEV', !!preg_match('/\.dev$/', $_SERVER['HTTP_HOST']));
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', LEAFPUB_DEV ? 1 : 0);
ini_set('log_errors', 1);

// Autoloader
$loader = require_once __DIR__ . '/vendor/autoload.php';
$loader->setPsr4('Leafpub\\', __DIR__ . '/classes');
$loader->setPsr4('Leafpub\\Plugins\\', __DIR__ . '/../content/plugins');
$loader->register();

// Check PHP version
if(version_compare(PHP_VERSION, '5.6.0') < 0) {
    exit('Leafpub requires PHP 5.6 or above.');
}

// Check for curl extension
if(!extension_loaded('curl')) {
    exit('Leafpub requires the curl extension.');
}

// Check for GD extension
if(!extension_loaded('gd')) {
    exit('Leafpub requires the GD extension.');
}

// Check for mbstring extension
if(!extension_loaded('mbstring')) {
    exit('Leafpub requires the Multibyte String extension.');
}

// Check for OpenSSL extension
if(!extension_loaded('openssl')) {
    exit('Leafpub requires the OpenSSL extension.');
}

// Check for PDO MySQL extension
if(!extension_loaded('pdo_mysql')) {
    exit('Leafpub requires the PDO extension with the MySQL driver.');
}
