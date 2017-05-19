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
if (LEAFPUB_DEV){
    $container['settings']['displayErrorDetails'] = true;
    $container['settings']['tracy'] = [
            'showPhpInfoPanel' => 1,
            'showSlimRouterPanel' => 1,
            'showSlimEnvironmentPanel' => 1,
            'showSlimRequestPanel' => 1,
            'showSlimResponsePanel' => 1,
            'showSlimContainer' => 1,
            'showEloquentORMPanel' => 0,
            'showTwigPanel' => 0,
            'showIdiormPanel' => 0,// > 0 mean you enable logging
            // but show or not panel you decide in browser in panel selector
            'showDoctrinePanel' => 'em',// here also enable logging and you must enter your Doctrine container name
            // and also as above show or not panel you decide in browser in panel selector
            'showProfilerPanel' => 1,
            'showVendorVersionsPanel' => 1,
            'showXDebugHelper' => 0,
            'showIncludedFiles' => 1,
            'showConsolePanel' => 0,
            'configs' => [
                // XDebugger IDE key
                'XDebugHelperIDEKey' => 'PHPSTORM',
                // Disable login (don't ask for credentials, be careful) values( 1 || 0 )
                'ConsoleNoLogin' => 0,
                // Multi-user credentials values( ['user1' => 'password1', 'user2' => 'password2'] )
                'ConsoleAccounts' => [
                    'dev' => '34c6fceca75e456f25e7e99531e2425c6c1de443'// = sha1('dev')
                ],
                // Password hash algorithm (password must be hashed) values('md5', 'sha256' ...)
                'ConsoleHashAlgorithm' => 'sha1',
                // Home directory (multi-user mode supported) values ( var || array )
                // '' || '/tmp' || ['user1' => '/home/user1', 'user2' => '/home/user2']
                'ConsoleHomeDirectory' => DIR,
                // terminal.js full URI
                'ConsoleTerminalJs' => '/assets/js/jquery.terminal.min.js',
                // terminal.css full URI
                'ConsoleTerminalCss' => '/assets/css/jquery.terminal.min.css',
                'ProfilerPanel' => [
                    // Memory usage 'primaryValue' set as Profiler::enable() or Profiler::enable(1)
                    'primaryValue' =>                   'absolute',    // or 'absolute'
                    'show' => [
                        'memoryUsageChart' => 1, // or false
                        'shortProfiles' => true, // or false
                        'timeLines' => true // or false
                    ]
                ]
            ]
        ];
}
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
