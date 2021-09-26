<?php
namespace Leafpub;

define('LEAFPUB_VERSION', '{{version}}');
define('LEAFPUB_SCHEME_VERSION', '3');
define('LEAFPUB_DEV', true); #!!preg_match('/\.test$/', $_SERVER['HTTP_HOST']));
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', LEAFPUB_DEV);
ini_set('log_errors', 1);

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0') < 0) {
    exit('Leafpub requires PHP 7.4 or above.');
}

// Check for curl extension
if (!extension_loaded('curl')) {
    exit('Leafpub requires the curl extension.');
}

// Check for GD extension
if (!extension_loaded('gd')) {
    exit('Leafpub requires the GD extension.');
}

// Check for OpenSSL extension
if (!extension_loaded('openssl')) {
    exit('Leafpub requires the OpenSSL extension.');
}

// Check for PDO MySQL extension
if (!extension_loaded('pdo_mysql')) {
    exit('Leafpub requires the PDO extension with the MySQL driver.');
}

if (!extension_loaded('zip')) {
    exit('Leafpub requires the ZIP extension.');
}
