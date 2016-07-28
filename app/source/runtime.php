<?php
namespace Postleaf;

define('POSTLEAF_VERSION', '{{version}}');
define('POSTLEAF_DEV', !!preg_match('/\.dev$/', $_SERVER['HTTP_HOST']));
error_reporting(POSTLEAF_DEV ? E_ALL & ~E_NOTICE : 0);

// Autoloader
$loader = require_once(__DIR__ . '/vendor/autoload.php');
$loader->setPsr4('Postleaf\\', __DIR__ . '/classes');
$loader->register();

// Check PHP version
if(version_compare(PHP_VERSION, '5.4.0') < 0) {
    exit('Postleaf requires PHP 5.4 or above.');
}

// Check for GD extension
if(!extension_loaded('gd')) {
    exit('Postleaf requires the GD extension.');
}

// Check for mbstring extension
if(!extension_loaded('mbstring')) {
    exit('Postleaf requires the Multibyte String extension.');
}

// Check for OpenSSL extension
if(!extension_loaded('openssl')) {
    exit('Postleaf requires the OpenSSL extension.');
}

// Check for PDO MySQL extension
if(!extension_loaded('pdo_mysql')) {
    exit('Postleaf requires the PDO extension with the MySQL driver.');
}