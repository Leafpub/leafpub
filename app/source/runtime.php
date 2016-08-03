<?php
namespace Postleaf;

define('POSTLEAF_VERSION', '{{version}}');
define('POSTLEAF_DEV', !!preg_match('/\.dev$/', $_SERVER['HTTP_HOST']));
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', POSTLEAF_DEV ? 1 : 0);
ini_set('log_errors', 1);

// Autoloader
$loader = require_once __DIR__ . '/vendor/autoload.php';
$loader->setPsr4('Postleaf\\', __DIR__ . '/classes');
$loader->register();

// Check PHP version
if(version_compare(PHP_VERSION, '5.5.0') < 0) {
    exit('Postleaf requires PHP 5.5 or above.');
}

// Check for curl extension
if(!extension_loaded('curl')) {
    exit('Postleaf requires the curl extension.');
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