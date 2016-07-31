<?php
namespace Postleaf;

define('POSTLEAF_VERSION', '{{version}}');
define('POSTLEAF_DEV', !!preg_match('/\.dev$/', $_SERVER['HTTP_HOST']));
error_reporting(POSTLEAF_DEV ? E_ALL & ~E_NOTICE : 0);

// Autoloader
$loader = require_once __DIR__ . '/vendor/autoload.php';
$loader->setPsr4('Postleaf\\', __DIR__ . '/classes');
$loader->register();
