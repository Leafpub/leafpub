<?php
declare(strict_types=1);

namespace Leafpub\Subscriber;

use Leafpub\Controller\Admin\DashboardController;
use Leafpub\Controller\Admin\EditPostController;
use Leafpub\Controller\Admin\EditTagController;
use Leafpub\Controller\Admin\EditUserController;
use Leafpub\Controller\Admin\ImportController;
use Leafpub\Controller\Admin\ListPostsController;
use Leafpub\Controller\Admin\ListTagsController;
use Leafpub\Controller\Admin\ListUsersController;
use Leafpub\Controller\Admin\LoginController;
use Leafpub\Controller\Admin\LogoutController;
use Leafpub\Controller\Admin\MediaController;
use Leafpub\Controller\Admin\NavigationController;
use Leafpub\Controller\Admin\NewPostController;
use Leafpub\Controller\Admin\NewTagController;
use Leafpub\Controller\Admin\NewUserController;
use Leafpub\Controller\Admin\PluginController;
use Leafpub\Controller\Admin\PostHistoryController;
use Leafpub\Controller\Admin\RecoverController;
use Leafpub\Controller\Admin\ResetController;
use Leafpub\Controller\Admin\SettingsController;
use Leafpub\Controller\Admin\UpdateController;
use Leafpub\Controller\Api\Get\RegenerateThumbnailsController;
use Leafpub\Controller\Frontend\AuthorController;
use Leafpub\Controller\Frontend\BlogController;
use Leafpub\Controller\Frontend\CustomHomepageController;
use Leafpub\Controller\Frontend\FeedController;
use Leafpub\Controller\Frontend\PostController;
use Leafpub\Controller\Frontend\SearchController;
use Leafpub\Controller\Frontend\SitemapController;
use Leafpub\Controller\Frontend\TagController;
use Leafpub\Events\Application\Shutdown;
use Leafpub\Events\Application\Startup;
use Leafpub\Middleware\AdjustSearchQueryMiddleware;
use Leafpub\Middleware\AuthMiddleware;
use Leafpub\Middleware\ImageMiddleware;
use Leafpub\Middleware\MaintenanceMiddleware;
use Leafpub\Middleware\PageNumbersMiddleware;
use Leafpub\Middleware\RemoveTrailingSlashMiddleware;
use Leafpub\Models\Setting;
use RunTracy\Middlewares\TracyMiddleware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ApplicationSubscriber
 * @package Leafpub\Subscriber
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class ApplicationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            Startup::class => [
                ['addRoutes'],
                ['addMiddleware']
            ],
            Shutdown::class => [],
        ];
    }

    public function addRoutes(Startup $evt): void
    {
        $app = $evt->getApp();

        // Get base slugs from settings
        $frags = (object) [
            'admin' => Setting::getOne('frag_admin'),
            'author' => Setting::getOne('frag_author'),
            'blog' => Setting::getOne('frag_blog'),
            'feed' => Setting::getOne('frag_feed'),
            'page' => Setting::getOne('frag_page'),
            'search' => Setting::getOne('frag_search'),
            'tag' => Setting::getOne('frag_tag')
        ];

        /**
         * API routes
         **/

        /** Unprotected **/
        $app->group("/api", function() {
            // Auth
            $this->post('/login', 'Leafpub\Controller\APIController:login');
            $this->post('/login/recover', 'Leafpub\Controller\APIController:recover');
            $this->post('/login/reset', 'Leafpub\Controller\APIController:reset');
        });

        /** Protected **/
        $app->group("/api", function() {
            // Importer
            $this->post('/import', 'Leafpub\Controller\APIController:handleImportUpload');
            $this->put('/import', 'Leafpub\Controller\APIController:doImport');

            // Posts
            $this->get('/posts', 'Leafpub\Controller\APIController:getPosts');
            $this->post('/posts', 'Leafpub\Controller\APIController:addPost');
            $this->put('/posts/{slug}', 'Leafpub\Controller\APIController:updatePost');
            $this->delete('/posts/{slug}', 'Leafpub\Controller\APIController:deletePost');
            $this->get('/posts/render', 'Leafpub\Controller\APIController:renderPost');
            $this->post('/posts/render', 'Leafpub\Controller\APIController:renderPost');
            $this->get('/posts/unlock/{slug}', 'Leafpub\Controller\APIController:unlockPost');

            // Plugins
            $this->get('/plugins', 'Leafpub\\Controller\\APIController:getPlugins');
            $this->post('/plugins', 'Leafpub\\Controller\\APIController:uploadPlugin');
            $this->put('/plugins', 'Leafpub\Controller\APIController:activatePlugin');
            $this->delete('/plugins/{plugin}', 'Leafpub\Controller\APIController:deletePlugin');

            // History
            $this->get('/history/{id}', 'Leafpub\Controller\APIController:getHistory');
            $this->delete('/history/{id}', 'Leafpub\Controller\APIController:deleteHistory');

            // Tags
            $this->get('/tags', 'Leafpub\Controller\APIController:getTags');
            $this->post('/tags', 'Leafpub\Controller\APIController:addTag');
            $this->put('/tags/{slug}', 'Leafpub\Controller\APIController:updateTag');
            $this->delete('/tags/{slug}', 'Leafpub\Controller\APIController:deleteTag');

            // Navigation
            $this->put('/navigation', 'Leafpub\Controller\APIController:updateNavigation');

            // Users
            $this->get('/users', 'Leafpub\Controller\APIController:getUsers');
            $this->post('/users', 'Leafpub\Controller\APIController:addUser');
            $this->put('/users/{slug}', 'Leafpub\Controller\APIController:updateUser');
            $this->delete('/users/{slug}', 'Leafpub\Controller\APIController:deleteUser');

            // Settings
            $this->post('/settings', 'Leafpub\Controller\APIController:updateSettings');
            $this->delete('/settings/cache', 'Leafpub\Controller\APIController:deleteCache');

            // Backups
            $this->post('/backup', 'Leafpub\Controller\APIController:addBackup');
            $this->delete('/backup/{file}', 'Leafpub\Controller\APIController:deleteBackup');
            $this->get('/backup/{file}', 'Leafpub\Controller\APIController:getBackup');
            $this->post('/backup/{file}/restore', 'Leafpub\Controller\APIController:restoreBackup');

            // Locater
            $this->get('/locater', 'Leafpub\Controller\APIController:getLocater');

            // Uploads
            $this->post('/uploads', 'Leafpub\Controller\APIController:addUpload');
            $this->get('/uploads', 'Leafpub\Controller\APIController:getUploads');
            $this->put('/uploads/{file}', 'Leafpub\Controller\APIController:editUpload');
            $this->delete('/uploads/{file}', 'Leafpub\Controller\APIController:deleteUpload');
            $this->get('/upload/{file}', 'Leafpub\Controller\APIController:getUpload');

            // Utilities
            $this->get('/oembed', 'Leafpub\Controller\APIController:getOembed');

            // Database update
            $this->post('/update', 'Leafpub\Controller\APIController:updateLeafpubDatabase');
            $this->get('/update-check', 'Leafpub\Controller\APIController:updateCheck');
            $this->patch('/update', 'Leafpub\Controller\APIController:runUpdate');

            $this->post('/dashboard', 'Leafpub\Controller\APIController:setDashboard');

            $this->get('/widget', 'Leafpub\Controller\APIController:getWidget');

        })->add($app->getContainer()->get(AuthMiddleware::class))->add('Leafpub\Middleware:checkDBScheme');

        /**
         * Admin views
         **/

        /** Unprotected **/
        $app->group("/$frags->admin", function() {
            $this->get('/login', LoginController::class);
            $this->get('/login/recover', RecoverController::class);
            $this->get('/login/reset', ResetController::class);
            $this->get('/logout', LogoutController::class);
        });

        /** Protected **/
        $app->group("/$frags->admin", function() {
            // Dashboard
            $this->get('', DashboardController::class);

            // Importer
            $this->get('/import', ImportController::class);

            // Posts
            $this->get('/posts', ListPostsController::class);
            $this->get('/posts/new', NewPostController::class);
            $this->get('/posts/{slug}', EditPostController::class);
            $this->get('/posts/{slug}/history/{id}', PostHistoryController::class);

            // Tags
            $this->get('/tags', ListTagsController::class);
            $this->get('/tags/new', NewTagController::class);
            $this->get('/tags/{slug}', EditTagController::class);

            // Navigation
            $this->get('/navigation', NavigationController::class);

            // Users
            $this->get('/users', ListUsersController::class);
            $this->get('/users/new', NewUserController::class);
            $this->get('/users/{slug}', EditUserController::class);

            // Settings
            $this->get('/settings', SettingsController::class);

            // Plugins
            $this->get('/plugins', PluginController::class);

            // Uploads
            $this->get('/uploads', MediaController::class);
            $this->get('/regenerateThumbnails', RegenerateThumbnailsController::class);

            $this->get('/updateLeafpub', UpdateController::class);

        })->add($app->getContainer()->get(AuthMiddleware::class))->add('Leafpub\Middleware:checkDBScheme');

        /**
         * Theme views
         **/

        /** Homepage **/
        if(Setting::getOne('homepage')) {
            // Custom homepage
            $app->get('/', CustomHomepageController::class);

            // Blog at /blog
            $app->group("/$frags->blog", function() use($frags) {
                $this->get("[/$frags->page/{page:[0-9]+}]", BlogController::class);
            })->add($app->getContainer()->get(PageNumbersMiddleware::class));
        } else {
            // Blog at the homepage
            $app->group('/', function() use($frags) {
                $this->get("[$frags->page/{page:[0-9]+}]", BlogController::class);
            })->add($app->getContainer()->get(PageNumbersMiddleware::class));
        }

// Feed
        $app->get("/$frags->feed", FeedController::class);

// Authors
        $app->group("/$frags->author", function() use ($frags) {
            $this->get("/{author}[/$frags->page/{page:[0-9]+}]", AuthorController::class);
        })->add($app->getContainer()->get(PageNumbersMiddleware::class));

// Tags
        $app->group("/$frags->tag", function() use ($frags) {
            $this->get("/{tag}[/$frags->page/{page:[0-9]+}]", TagController::class);
        })->add($app->getContainer()->get(PageNumbersMiddleware::class));

// Search
        $app->group("/$frags->search", function() use($frags) {
            $this->get("[/{query}[/$frags->page/{page:[0-9]+}]]", SearchController::class);
        })->add($app->getContainer()->get(AdjustSearchQueryMiddleware::class))->add($app->getContainer()->get(PageNumbersMiddleware::class));

        $app->get('/sitemap', SitemapController::class);

// Posts
        $app->get('/{post}', PostController::class);
    }

    public function addMiddleware(Startup $evt): void
    {
        $leafpub = $evt->getApp();
        $container = $leafpub->getContainer();

        $leafpub->add(
            $container->get(RemoveTrailingSlashMiddleware::class)
        );

        $leafpub->add(
            $container->get(MaintenanceMiddleware::class)
        );

        $leafpub->add(
            $container->get(ImageMiddleware::class)
        );

        if (LEAFPUB_DEV) {
            $leafpub->add(new TracyMiddleware($leafpub));
            $leafpub->add($container->get(\Leafpub\Middleware\TracyMiddleware::class));
        }
    }
}