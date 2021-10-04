<?php
namespace Leafpub;

use Leafpub\Controller\Admin\DashboardController;
use Leafpub\Controller\Admin\EditPostController;
use Leafpub\Controller\Admin\ImportController;
use Leafpub\Controller\Admin\ListPostsController;
use Leafpub\Controller\Admin\LoginController;
use Leafpub\Controller\Admin\LogoutController;
use Leafpub\Controller\Admin\NewPostController;
use Leafpub\Controller\Admin\PostHistoryController;
use Leafpub\Controller\Admin\RecoverController;
use Leafpub\Controller\Admin\ResetController;
use Leafpub\Events\Application\Startup;
use Leafpub\Middleware\AdjustSearchQueryMiddleware;
use Leafpub\Middleware\AuthMiddleware;
use Leafpub\Middleware\ImageMiddleware;
use Leafpub\Middleware\MaintenanceMiddleware;
use Leafpub\Middleware\PageNumbersMiddleware;
use Leafpub\Middleware\RemoveTrailingSlashMiddleware;
use Leafpub\Middleware\TracyMiddleware;
use Leafpub\Subscriber\Application;
use Leafpub\Subscriber\ApplicationSubscriber;
use Leafpub\Subscriber\MediaSubscriber;
use Leafpub\Subscriber\PostSubscriber;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Slim\Container;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

function containerFactory(): ContainerInterface
{
    $container = new Container();
    $container['settings']['routerCacheFile'] = __DIR__ . DIRECTORY_SEPARATOR . '../var/cache/routes.cache';
    if (LEAFPUB_DEV) {
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
                'ConsoleHomeDirectory' => __DIR__,
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

    $container['notFoundHandler'] = static function (ContainerInterface $container) {
        return static function (RequestInterface $request, ResponseInterface $response) use ($container) {
            return $response->withStatus(404)->write(Error::render());
        };
    };

    // Not allowed handler
    $container['notAllowedHandler'] = static function (ContainerInterface $container) {
        return static function (RequestInterface $request, ResponseInterface $response, $methods) use ($container) {
            return $response->withStatus(405)->write(Error::system([
                'title' => 'Method Not Allowed',
                'message' => 'Method must be one of: ' . implode(', ', $methods)
            ]));
        };
    };

    // Error handlers
    $container['errorHandler'] = static function (ContainerInterface $container) {
        return static function (RequestInterface $request, ResponseInterface $response, \Throwable $exception) use ($container) {
            return $response->withStatus(500)->write(Error::system([
                'title' => 'Application Error',
                'message' => $exception->getMessage()
            ]));
        };
    };

    $container['dispatcher'] = static function (ContainerInterface $container): EventDispatcherInterface {
        return new EventDispatcher();
    };

    $container['logger'] = static function (ContainerInterface $container): LoggerInterface {
        return new Logger(
            'Leafpub-Logger',
            $container['log-handlers'],
            $container['log-processors']
        );
    };

    $container['log-handlers'] = static function (ContainerInterface $container): array {
        $handlers = [
            new RotatingFileHandler(
                $container['log-path'],
                30,
                $container['log-level']
            ),
        ];

        return $handlers;
    };

    $container['log-processors'] = static function (ContainerInterface $container): array {
        $processors = [];
        if (LEAFPUB_DEV) {
            $processors[] = new IntrospectionProcessor();
        }
        return $processors;
    };

    $container['log-level'] = (LEAFPUB_DEV ? Logger::DEBUG : Logger::INFO);

    $container['log-path'] = __DIR__ . '../var/log';

    /** BEGIN MIDDLEWARES */
    $container[AdjustSearchQueryMiddleware::class] = static function (ContainerInterface $container): AdjustSearchQueryMiddleware {
        return new AdjustSearchQueryMiddleware();
    };

    $container[AuthMiddleware::class] = static function (ContainerInterface $container): AuthMiddleware {
        return new AuthMiddleware();
    };

    $container[ImageMiddleware::class] = static function (ContainerInterface $container): ImageMiddleware {
        return new ImageMiddleware();
    };

    $container[MaintenanceMiddleware::class] = static function (ContainerInterface $container): MaintenanceMiddleware {
        return new MaintenanceMiddleware();
    };

    $container[PageNumbersMiddleware::class] = static function (ContainerInterface $container): PageNumbersMiddleware {
        return new PageNumbersMiddleware();
    };

    $container[RemoveTrailingSlashMiddleware::class] = static function (ContainerInterface $container): RemoveTrailingSlashMiddleware {
        return new RemoveTrailingSlashMiddleware();
    };

    $container[TracyMiddleware::class] = static function (ContainerInterface $container): TracyMiddleware {
        return new TracyMiddleware();
    };
    /** END MIDDLEWARES */

    /** BEGIN EVENT SUBSCRIBER */
    $container[ApplicationSubscriber::class] = static function (ContainerInterface $container): ApplicationSubscriber {
        return new ApplicationSubscriber();
    };

    $container[PostSubscriber::class] = static function (ContainerInterface $container): PostSubscriber {
        return new PostSubscriber();
    };

    $container[MediaSubscriber::class] = static function (ContainerInterface $container): MediaSubscriber {
        return new MediaSubscriber();
    };

    /** END SUBSCRIBER */

    /** BEGINN CONTROLLER */

    $container[LoginController::class] = static function (ContainerInterface $container): LoginController {
        return new LoginController();
    };

    $container[RecoverController::class] = static function (ContainerInterface $container): RecoverController {
        return new RecoverController();
    };

    $container[ResetController::class] = static function (ContainerInterface $container): ResetController {
        return new ResetController();
    };

    $container[LogoutController::class] = static function (ContainerInterface $container): LogoutController {
        return new LogoutController();
    };

    $container[DashboardController::class] = static function (ContainerInterface $container): DashboardController {
        return new DashboardController();
    };

    $container[ImportController::class] = static function (ContainerInterface $container): ImportController {
        return new ImportController();
    };

    $container[ListPostsController::class] = static function (ContainerInterface $container): ListPostsController {
        return new ListPostsController();
    };

    $container[NewPostController::class] = static function (ContainerInterface $container): NewPostController {
        return new NewPostController();
    };

    $container[EditPostController::class] = static function (ContainerInterface $container): EditPostController {
        return new EditPostController();
    };

    $container[PostHistoryController::class] = static function (ContainerInterface $container): PostHistoryController {
        return new PostHistoryController();
    };

    /** END CONTROLLER */
    return $container;
}
