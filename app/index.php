<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
#namespace Leafpub;

use Leafpub\{Leafpub, Session};

use Leafpub\App;
use Slim\Container;
use function Leafpub\containerFactory;

require __DIR__ . '/source/runtime.php';

// Initialize the app and session
Leafpub::run();
Session::init();

// Initialize the app

$app = new App(containerFactory());

#require __DIR__ . '/source/config/routes.php';
#require __DIR__ . '/source/config/middleware.php';
#require __DIR__ . '/source/config/dependencies.php';

Leafpub::registerPlugins($app);
// Create startup event and dispatch...
#$startup = new Startup($app);
#Leafpub::dispatchEvent(Startup::NAME, $startup);

// Run it!
$app->run();
