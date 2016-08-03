<?php
//
// Controller for admin panel views
//
namespace Postleaf\Controller;

use Postleaf\Admin,
    Postleaf\Backup,
    Postleaf\Blog,
    Postleaf\Cache,
    Postleaf\Error,
    Postleaf\Feed,
    Postleaf\History,
    Postleaf\Language,
    Postleaf\Post,
    Postleaf\Postleaf,
    Postleaf\Renderer,
    Postleaf\Search,
    Postleaf\Session,
    Postleaf\Setting,
    Postleaf\Tag,
    Postleaf\Theme,
    Postleaf\Upload,
    Postleaf\User;

class AdminController extends Controller {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Auth
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // GET admin/login
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

    // GET admin/login/recover
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

    // GET admin/login/reset
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

    // GET admin/logout
    public function logout($request, $response, $args) {
        Session::logout();
        return $response->withRedirect(Admin::url('login'));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Dashboard
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // admin/
    public function dashboard($request, $response, $args) {
        return $response->withRedirect(Admin::url('posts'));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Posts
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // GET admin/posts
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

    // GET admin/posts/new
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
            'frame_src' => Postleaf::url(
                'api/posts/render?new&zen=' . rawurlencode($_COOKIE['zen'])
            )
        ]);

        return $response->write($html);
    }

    // GET admin/posts/{slug}
    public function editPost($request, $response, $args) {
        $post = Post::get($args['slug']);
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

        $html = Admin::render('posts.edit', [
            'title' => Language::term('edit_post'),
            'scripts' => ['editor.min.js', 'posts.edit.min.js'],
            'styles' => 'posts.edit.css',
            'post' => $post,
            'history' => History::getAll($post['slug']),
            'authors' => User::getNames(),
            'all_tags' => Tag::getNames(),
            'post_tags' => $post['tags'],
            'can_create_tags' => Session::isRole(['owner', 'admin', 'editor']) ? 'true' : 'false',
            'frame_src' => Postleaf::url(
                'api/posts/render?post=' . rawurlencode($post['slug']) .
                '&zen=' . rawurlencode($_COOKIE['zen'])
            )
        ]);

        return $response->write($html);
    }

    // GET admin/posts/{slug}/history/{id}
    public function history($request, $response, $args) {
        $history = History::get($args['id']);
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

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Tags
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // GET admin/tags
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

    // GET admin/tags/new
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

    // GET admin/tags/{slug}
    public function editTag($request, $response, $args) {
        // To edit tags, you must be an owner, admin, or editor
        if(!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $tag = Tag::get($args['slug']);
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

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Navigation
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // admin/navigation
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
            'navigation' => json_decode(Setting::get('navigation'), true)
        ]);

        return $response->write($html);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Users
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // GET admin/users
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

    // GET admin/users/add
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

    // GET admin/users/{slug}
    public function editUser($request, $response, $args) {
        // To edit a user, you must be an owner, admin or the user
        if(!Session::isRole(['owner', 'admin']) && $args['slug'] !== Session::user('slug')) {
            return $this->notFound($request, $response);
        }

        // Get the user
        $user = User::get($args['slug']);
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

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Admin
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // admin/settings
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

        $html = Admin::render('settings', [
            'title' => Language::term('settings'),
            'scripts' => 'settings.min.js',
            'styles' => 'settings.css',
            'pages' => $pages,
            'backups' => Backup::getAll(),
            'languages' => Language::getAll(),
            'timezones' => $timezones,
            'themes' => Theme::getAll()
        ]);

        return $response->write($html);
    }

}