<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Controller;

use Leafpub\Admin;
use Leafpub\Backup;
use Leafpub\Blog;
use Leafpub\Language;
use Leafpub\Leafpub;
use Leafpub\Mailer;
use Leafpub\Models\History;
use Leafpub\Models\Plugin;
use Leafpub\Models\Post;
use Leafpub\Models\Setting;
use Leafpub\Models\Tag;
use Leafpub\Models\Upload;
use Leafpub\Models\User;
use Leafpub\Search;
use Leafpub\Session;
use Leafpub\Theme;
use Leafpub\Widget;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * AdminController
 *
 * This class handles all non-api requests in the Leafpub backend.
 * It's the controller for the admin panel views.
 *
 **/
class AdminController extends Controller
{











    public function plugins(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }
        $html = Admin::render('plugins', [
                'title' => Language::term('plugins'),
                'scripts' => 'plugins.min.js',
                'styles' => 'plugins.css',
                'plugins' => Plugin::getMany(),
            ]);

        return $response->write($html);
    }









    /**
     * Renders the tags view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function tags(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // To view tags, you must be an owner, admin, or editor
        if (!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('tags', [
            'title' => Language::term('tags'),
            'scripts' => 'tags.min.js',
            'styles' => 'tags.css',
            'tags' => Tag::getMany([
                'items_per_page' => 50,
            ]),
        ]);

        return $response->write($html);
    }

    /**
     * Renders the new tag view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function newTag(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // To add tags, you must be an owner, admin, or editor
        if (!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('tags.new', [
            'title' => Language::term('new_tag'),
            'scripts' => 'tags.edit.min.js',
            'styles' => 'tags.edit.css',
            'tag' => [],
        ]);

        return $response->write($html);
    }

    /**
     * Renders the edit tag view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function editTag(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // To edit tags, you must be an owner, admin, or editor
        if (!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $tag = Tag::getOne($args['slug']);
        if (!$tag) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('tags.edit', [
            'title' => Language::term('edit_tag'),
            'scripts' => 'tags.edit.min.js',
            'styles' => 'tags.edit.css',
            'tag' => $tag,
        ]);

        return $response->write($html);
    }

    /**
     * Renders the navigation view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function navigation(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $params = $request->getParams();

        // To view navigation, you must be an owner or admin
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('navigation', [
            'title' => Language::term('navigation'),
            'scripts' => 'navigation.min.js',
            'styles' => 'navigation.css',
            'navigation' => json_decode(Setting::getOne('navigation'), true),
        ]);

        return $response->write($html);
    }

    /**
     * Renders the user view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function users(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // To view users, you must be an owner or admin
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('users', [
            'title' => Language::term('users'),
            'scripts' => 'users.min.js',
            'styles' => 'users.css',
            'users' => User::getMany([
                'items_per_page' => 50,
            ]),
        ]);

        return $response->write($html);
    }

    /**
     * Renders the new user view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function newUser(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // To add users, you must be an owner or admin
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('users.new', [
            'title' => Language::term('new_user'),
            'scripts' => 'users.edit.min.js',
            'styles' => 'users.edit.css',
            'user' => [],
            'redirect' => Admin::url('users'),
        ]);

        return $response->write($html);
    }

    /**
     * Renders the edit user view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function editUser(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // To edit a user, you must be an owner, admin or the user
        if (!Session::isRole(['owner', 'admin']) && $args['slug'] !== Session::user('slug')) {
            return $this->notFound($request, $response);
        }

        // Get the user
        $user = User::getOne($args['slug']);
        if (!$user) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('users.edit', [
            'title' => Language::term('edit_user'),
            'scripts' => 'users.edit.min.js',
            'styles' => 'users.edit.css',
            'user' => $user,
            'redirect' => Session::isRole(['owner', 'admin']) ?
                Admin::url('users') : Admin::url('posts'),
        ]);

        return $response->write($html);
    }

    /**
     * Renders the settings view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function settings(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // To edit settings, you must be an owner or admin
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        // Get list for custom pages field and sort by title
        $pages = Post::getMany([
            'ignore_posts' => true,
            'ignore_pages' => false,
            'items_per_page' => 100,
        ]);
        usort($pages, function ($a, $b) {
            return $a['title'] > $b['title'];
        });

        // Get timezones
        $timezones = [];
        foreach (\DateTimeZone::listIdentifiers() as $tz) {
            $timezones[] = [
                'code' => $tz,
                'name' => str_replace(['_', '/'], [' ', ' / '], $tz),
            ];
        }

        // Get mailers
        $mailers = [];
        foreach (Mailer::getMailers() as $mailerName => $mailer) {
            $mailers[] = [
                'tag' => $mailerName,
                'name' => $mailer['name'],
            ];
        }

        $html = Admin::render('settings', [
            'title' => Language::term('settings'),
            'scripts' => 'settings.min.js',
            'styles' => 'settings.css',
            'pages' => $pages,
            'mailers' => $mailers,
            'backups' => Backup::getAll(),
            'languages' => Language::getAll(),
            'timezones' => $timezones,
            'themes' => Theme::getAll(),
        ]);

        return $response->write($html);
    }

    public function uploads(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $uploads = Upload::getMany([
            'items_per_page' => 20,
        ], $pagination);

        $html = Admin::render('uploads', [
            'title' => Language::term('uploads'),
            'scripts' => 'uploads.min.js',
            'styles' => 'uploads.css',
            'uploads' => $uploads,
            'all_tags' => Tag::getNames('upload'),
            'post_tags' => [],
            'can_create_tags' => Session::isRole(['owner', 'admin', 'editor']) ? 'true' : 'false',
        ]);

        return $response->write($html);
    }

    public function regenerateThumbnails(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $generatedThumbnails = Upload::regenerateThumbnails();
        // Send response
        return $response->withJson([
            'success' => true,
            'num' => $generatedThumbnails,
        ]);
    }

    public function updateLeafpub(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        if (!Session::isRole(['owner', 'admin'])) {
            return $response->withRedirect(Admin::url());
        }

        if (version_compare(LEAFPUB_SCHEME_VERSION, (\Leafpub\Models\Setting::getOne('schemeVersion') ?: 0)) < 1) {
            return $response->withRedirect(Admin::url());
        }

        $html = Admin::render('update', [
            'title' => Language::term('update'),
            'scripts' => 'update.min.js',
            //'styles' => 'update.css',
            'dbScheme' => \Leafpub\Models\Setting::getOne('schemeVersion') ?: 0,
            'schemeVersion' => LEAFPUB_SCHEME_VERSION,
        ]);

        return $response->write($html);
    }
}
