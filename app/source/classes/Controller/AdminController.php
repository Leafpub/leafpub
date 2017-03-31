<?php
/**
 * Leafpub (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
namespace Leafpub\Controller;

use Leafpub\Admin,
    Leafpub\Backup,
    Leafpub\Blog,
    Leafpub\Cache,
    Leafpub\Error,
    Leafpub\Feed,
    Leafpub\Language,
    Leafpub\Leafpub,
    Leafpub\Renderer,
    Leafpub\Search,
    Leafpub\Session,
    Leafpub\Theme,
    Leafpub\Importer,
    Leafpub\Mailer,
    Leafpub\Widget,
    Leafpub\Models\History,
    Leafpub\Models\Post,
    Leafpub\Models\Setting,
    Leafpub\Models\Tag,
    Leafpub\Models\Upload,
    Leafpub\Models\User,
    Leafpub\Models\Plugin;

/**
* AdminController
*
* This class handles all non-api requests in the Leafpub backend.
* It's the controller for the admin panel views.
*
**/
class AdminController extends Controller {

    /**
    * Renders the login view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function login($request, $response, $args) {
        $params = $request->getParams();

        // Redirect to the admin page if they're already logged in
        if(Session::isAuthenticated()) {
            return $response->withRedirect(Admin::url());
        }

        $html = Admin::render('login', [
            'title' => Language::term('login'),
            'scripts' => ['login.min.js'],
            'styles' => 'login.css',
            'body_class' => 'no-menu',
            'redirect' => $params['redirect']
        ]);

        return $response->write($html);
    }

    /**
    * Renders the password recover view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function recover($request, $response, $args) {
        // Log out the current user if they requested this page
        Session::logout();

        $html = Admin::render('login.recover', [
            'title' => Language::term('lost_your_password'),
            'scripts' => ['login.min.js'],
            'styles' => 'login.css',
            'body_class' => 'no-menu'
        ]);

        return $response->write($html);
    }

    /**
    * Renders the password reset view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function reset($request, $response, $args) {
        // Log out the current user if they requested this page
        Session::logout();

        $html = Admin::render('login.reset', [
            'title' => Language::term('lost_your_password'),
            'scripts' => ['login.min.js'],
            'styles' => 'login.css',
            'body_class' => 'no-menu'
        ]);

        return $response->write($html);
    }

    /**
    * Logout the current user (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function logout($request, $response, $args) {
        Session::logout();
        return $response->withRedirect(Admin::url('login'));
    }

    /**
    * Show the import page
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    *
    **/
    public function import($request, $response, $args){
        // Only the blog owner can import another blog
        if (!Session::isRole('owner')){
            return $this->notFound($request, $response);
        }

        // Search for available dropins
        foreach (Leafpub::scanDir(Leafpub::path('source/classes/Importer/Dropins/')) as $file){
            $installedImporter[] = Leafpub::filename($file->getFilename());
        }
        
        $html = Admin::render('import', [
            'title' => Language::term('import'),
            'scripts' => 'import.min.js',
            'styles' => 'import.css',
            'dropins' => $installedImporter
        ]);

        return $response->write($html);
    }

    /**
    * Redirects admin/ to admin/posts (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function dashboard($request, $response, $args) {
        if (\Leafpub\Models\Setting::getOne('showDashboard') === 'on'){
            $html = Admin::render('dashboard', [
                'title' => Language::term('dashboard'),
                'scripts' => 'dashboard.min.js',
                'styles' => 'dashboard.css',
                'dashboard' => Widget::renderDashboard(Session::user('slug')),
                //'widgets' => Widget::getWidgets()
            ]);

            return $response->write($html);
        } else {
            return $response->withRedirect(Admin::url('posts'));
        }
    }

    public function plugins($request, $response, $args){
        if (!Session::isRole(['owner', 'admin'])){
            return $this->notFound($request, $response);
        } else {
            $html = Admin::render('plugins', [
                'title' => Language::term('plugins'),
                'scripts' => 'plugins.min.js',
                'styles' => 'plugins.css',
                'plugins' => Plugin::getMany()
            ]);

            return $response->write($html);
        }
    }

    /**
    * Renders the posts view view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function posts($request, $response, $args) {
        $html = Admin::render('posts', [
            'title' => Language::term('posts'),
            'scripts' => 'posts.min.js',
            'styles' => 'posts.css',
            'posts' => Post::getMany([
                // If you're not an owner, admin, or editor then you can only see your own posts
                'author' =>
                    Session::isRole(['owner', 'admin', 'editor']) ? null : Session::user('slug'),
                'status' => null,
                'ignore_featured' => false,
                'ignore_pages' => false,
                'ignore_sticky' => false,
                'items_per_page' => 50,
                'end_date' => null
            ], $pagination),
            'pagination' => $pagination
        ]);

        return $response->write($html);
    }

    /**
    * Renders the new post view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function newPost($request, $response, $args) {
        $html = Admin::render('posts.new', [
            'title' => Language::term('new_post'),
            'scripts' => ['editor.min.js', 'posts.edit.min.js'],
            'styles' => 'posts.edit.css',
            'post' => [],
            'history' => false,
            'authors' => User::getNames(),
            'all_tags' => Tag::getNames(),
            'post_tags' => [],
            'can_create_tags' => Session::isRole(['owner', 'admin', 'editor']) ? 'true' : 'false',
            'frame_src' => Leafpub::url(
                'api/posts/render?new&zen=' . rawurlencode($_COOKIE['zen'])
            )
        ]);

        return $response->write($html);
    }

    /**
    * Renders the edit post view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function editPost($request, $response, $args) {
        $post = Post::getOne($args['slug']);
        if(!$post) {
            return $this->notFound($request, $response);
        }

        // To edit a post, you must be an owner, admin, editor OR the owner of the post
        if(
            !Session::isRole(['owner', 'admin', 'editor']) &&
            $post['author'] !== Session::user('slug')
        ) {
            return $this->notFound($request, $response);
        }

        // Post is locked by another user
        if (isset($post['meta']['lock']) && $post['meta']['lock'][0] !== Session::user('slug')){
            return $this->notFound($request, $response);
        } elseif(!isset($post['meta']['lock'])){
            Post::lockPostForEdit($post['id']);
        }
        
        $html = Admin::render('posts.edit', [
            'title' => Language::term('edit_post'),
            'scripts' => ['editor.min.js', 'posts.edit.min.js'],
            'styles' => 'posts.edit.css',
            'post' => $post,
            'history' => History::getMany(['slug' => $post['slug']]),
            'authors' => User::getNames(),
            'all_tags' => Tag::getNames(),
            'post_tags' => $post['tags'],
            'can_create_tags' => Session::isRole(['owner', 'admin', 'editor']) ? 'true' : 'false',
            'frame_src' => Leafpub::url(
                'api/posts/render?post=' . rawurlencode($post['slug']) .
                '&zen=' . rawurlencode($_COOKIE['zen'])
            )
        ]);

        return $response->write($html);
    }

    /**
    * Renders the history of a post (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function history($request, $response, $args) {
        $history = History::getOne($args['id']);
        // Is there a history object and does the post's slug match the slug argument?
        if(!$history || $history['post_data']['slug'] !== $args['slug']) {
            return $this->notFound($request, $response);
        }

        // Render the revision
        $html = Post::render($history['post_data'], [
            'preview' => true
        ]);

        return $response->write($html);
    }

    /**
    * Renders the tags view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function tags($request, $response, $args) {
        // To view tags, you must be an owner, admin, or editor
        if(!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('tags', [
            'title' => Language::term('tags'),
            'scripts' => 'tags.min.js',
            'styles' => 'tags.css',
            'tags' => Tag::getMany([
                'items_per_page' => 50
            ])
        ]);

        return $response->write($html);
    }

    /**
    * Renders the new tag view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function newTag($request, $response, $args) {
        // To add tags, you must be an owner, admin, or editor
        if(!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('tags.new', [
            'title' => Language::term('new_tag'),
            'scripts' => 'tags.edit.min.js',
            'styles' => 'tags.edit.css',
            'tag' => []
        ]);

        return $response->write($html);
    }

    /**
    * Renders the edit tag view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function editTag($request, $response, $args) {
        // To edit tags, you must be an owner, admin, or editor
        if(!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $tag = Tag::getOne($args['slug']);
        if(!$tag) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('tags.edit', [
            'title' => Language::term('edit_tag'),
            'scripts' => 'tags.edit.min.js',
            'styles' => 'tags.edit.css',
            'tag' => $tag
        ]);

        return $response->write($html);
    }

    /**
    * Renders the navigation view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function navigation($request, $response, $args) {
        $params = $request->getParams();

        // To view navigation, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('navigation', [
            'title' => Language::term('navigation'),
            'scripts' => 'navigation.min.js',
            'styles' => 'navigation.css',
            'navigation' => json_decode(Setting::getOne('navigation'), true)
        ]);

        return $response->write($html);
    }

    /**
    * Renders the user view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function users($request, $response, $args) {
        // To view users, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('users', [
            'title' => Language::term('users'),
            'scripts' => 'users.min.js',
            'styles' => 'users.css',
            'users' => User::getMany([
                'items_per_page' => 50
            ])
        ]);

        return $response->write($html);
    }

    /**
    * Renders the new user view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function newUser($request, $response, $args) {
        // To add users, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('users.new', [
            'title' => Language::term('new_user'),
            'scripts' => 'users.edit.min.js',
            'styles' => 'users.edit.css',
            'user' => [],
            'redirect' => Admin::url('users')
        ]);

        return $response->write($html);
    }

    /**
    * Renders the edit user view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function editUser($request, $response, $args) {
        // To edit a user, you must be an owner, admin or the user
        if(!Session::isRole(['owner', 'admin']) && $args['slug'] !== Session::user('slug')) {
            return $this->notFound($request, $response);
        }

        // Get the user
        $user = User::getOne($args['slug']);
        if(!$user) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('users.edit', [
            'title' => Language::term('edit_user'),
            'scripts' => 'users.edit.min.js',
            'styles' => 'users.edit.css',
            'user' => $user,
            'redirect' => Session::isRole(['owner', 'admin']) ?
                Admin::url('users') : Admin::url('posts')
        ]);

        return $response->write($html);
    }

    /**
    * Renders the settings view (GET)
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function settings($request, $response, $args) {
        // To edit settings, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        // Get list for custom pages field and sort by title
        $pages = Post::getMany([
            'ignore_posts' => true,
            'ignore_pages' => false,
            'items_per_page' => 100
        ]);
        usort($pages, function($a, $b) {
            return $a['title'] > $b['title'];
        });

        // Get timezones
        $timezones = [];
        foreach(\DateTimeZone::listIdentifiers() as $tz) {
            $timezones[] = [
                'code' => $tz,
                'name' => str_replace(['_', '/'], [' ', ' / '], $tz)
            ];
        }

        // Get mailers
        $mailers = [];
        foreach (Mailer::getMailers() as $mailerName => $mailer) {
            $mailers[] = [
                'tag'  => $mailerName,
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
            'themes' => Theme::getAll()
        ]);

        return $response->write($html);
    }

    public function uploads($request, $response, $args){
        $uploads = Upload::getMany([
            'items_per_page' => 20
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

    public function regenerateThumbnails($request, $response, $args){
        $generatedThumbnails = Upload::regenerateThumbnails();
        // Send response
        return $response->withJson([
            'success' => true,
            'num' => $generatedThumbnails
        ]);
    }

    public function updateLeafpub($request, $response, $args){
        if (!Session::isRole(['owner', 'admin'])){
            return $response->withRedirect(Admin::url());
        }
        
        if (version_compare(LEAFPUB_SCHEME_VERSION, (\Leafpub\Models\Setting::getOne('schemeVersion') ?: 0)) < 1){
           return $response->withRedirect(Admin::url()); 
        }

        $html = Admin::render('update', [
            'title' => Language::term('update'),
            'scripts' => 'update.min.js',
            //'styles' => 'update.css',
            'dbScheme' => \Leafpub\Models\Setting::getOne('schemeVersion') ?: 0,
            'schemeVersion' => LEAFPUB_SCHEME_VERSION
        ]);

        return $response->write($html);
    }
}