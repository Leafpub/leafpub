<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
namespace Leafpub;
require __DIR__ . '/source/runtime.php';

// Initialize the app and session
Leafpub::run();
Session::init();

// Initialize the app
$container = new \Slim\Container();
$app = new \Slim\App($container);

require __DIR__ . '/source/config/routes.php';
require __DIR__ . '/source/config/middleware.php';
require __DIR__ . '/source/config/dependencies.php';

Leafpub::registerPlugins($app);
// Create startup event and dispatch...
$startup = new Events\Application\Startup($app);
Leafpub::dispatchEvent(Events\Application\Startup::NAME, $startup);

// Run it!
$app->run();
