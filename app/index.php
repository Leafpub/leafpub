<?php
/*
Leafpub: Simple, beautiful publishing.
Copyright 2016 A Beautiful Site, LLC

Website: https://www.leafpub.org/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
namespace Leafpub;
require __DIR__ . '/source/runtime.php';

// Initialize the app and session
Leafpub::run();
Session::init();

// Get base slugs from settings
$frags = (object) [
    'admin' => Setting::get('frag_admin'),
    'author' => Setting::get('frag_author'),
    'blog' => Setting::get('frag_blog'),
    'feed' => Setting::get('frag_feed'),
    'page' => Setting::get('frag_page'),
    'search' => Setting::get('frag_search'),
    'tag' => Setting::get('frag_tag')
];

// Initialize the app
$container = new \Slim\Container();
$app = new \Slim\App($container);
$app->add('Leafpub\Middleware:removeTrailingSlashes');
$app->add('Leafpub\Middleware::maintenance');

////////////////////////////////////////////////////////////////////////////////////////////////////
// API routes
////////////////////////////////////////////////////////////////////////////////////////////////////

// Unprotected
$app->group("/api", function() {
    // Auth
    $this->post('/login', 'Leafpub\Controller\APIController:login');
    $this->post('/login/recover', 'Leafpub\Controller\APIController:recover');
    $this->post('/login/reset', 'Leafpub\Controller\APIController:reset');
});

// Protected
$app->group("/api", function() {
    // Posts
    $this->get('/posts', 'Leafpub\Controller\APIController:getPosts');
    $this->post('/posts', 'Leafpub\Controller\APIController:addPost');
    $this->put('/posts/{slug}', 'Leafpub\Controller\APIController:updatePost');
    $this->delete('/posts/{slug}', 'Leafpub\Controller\APIController:deletePost');
    $this->get('/posts/render', 'Leafpub\Controller\APIController:renderPost');
    $this->post('/posts/render', 'Leafpub\Controller\APIController:renderPost');

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

    // Utilities
    $this->get('/oembed', 'Leafpub\Controller\APIController:getOembed');
})->add('Leafpub\Middleware:requireAuth');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Admin views
////////////////////////////////////////////////////////////////////////////////////////////////////

// Unprotected
$app->group("/$frags->admin", function() {
    $this->get('/login', 'Leafpub\Controller\AdminController:login');
    $this->get('/login/recover', 'Leafpub\Controller\AdminController:recover');
    $this->get('/login/reset', 'Leafpub\Controller\AdminController:reset');
    $this->get('/logout', 'Leafpub\Controller\AdminController:logout');
});

// Protected
$app->group("/$frags->admin", function() {
    // Dashboard
    $this->get('', 'Leafpub\Controller\AdminController:dashboard');

    // Posts
    $this->get('/posts', 'Leafpub\Controller\AdminController:posts');
    $this->get('/posts/new', 'Leafpub\Controller\AdminController:newPost');
    $this->get('/posts/{slug}', 'Leafpub\Controller\AdminController:editPost');
    $this->get('/posts/{slug}/history/{id}', 'Leafpub\Controller\AdminController:history');

    // Tags
    $this->get('/tags', 'Leafpub\Controller\AdminController:tags');
    $this->get('/tags/new', 'Leafpub\Controller\AdminController:newTag');
    $this->get('/tags/{slug}', 'Leafpub\Controller\AdminController:editTag');

    // Navigation
    $this->get('/navigation', 'Leafpub\Controller\AdminController:navigation');

    // Users
    $this->get('/users', 'Leafpub\Controller\AdminController:users');
    $this->get('/users/new', 'Leafpub\Controller\AdminController:newUser');
    $this->get('/users/{slug}', 'Leafpub\Controller\AdminController:editUser');

    // Settings
    $this->get('/settings', 'Leafpub\Controller\AdminController:settings');
})->add('Leafpub\Middleware:requireAuth');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Theme views
////////////////////////////////////////////////////////////////////////////////////////////////////

// Homepage
if(Setting::get('homepage')) {
    // Custom homepage
    $app->get('/', 'Leafpub\Controller\ThemeController:customHomepage');

    // Blog at /blog
    $app->group("/$frags->blog", function() use($frags) {
        $this->get("[/$frags->page/{page:[0-9]+}]", 'Leafpub\Controller\ThemeController:blog');
    })->add('Leafpub\Middleware:adjustPageNumbers');
} else {
    // Blog at the homepage
    $app->group('/', function() use($frags) {
        $this->get("[$frags->page/{page:[0-9]+}]", 'Leafpub\Controller\ThemeController:blog');
    })->add('Leafpub\Middleware:adjustPageNumbers');
}

// Feed
$app->get("/$frags->feed", 'Leafpub\Controller\ThemeController:feed');

// Authors
$app->group("/$frags->author", function() use ($frags) {
    $this->get("/{author}[/$frags->page/{page:[0-9]+}]", 'Leafpub\Controller\ThemeController:author');
})->add('Leafpub\Middleware:adjustPageNumbers');

// Tags
$app->group("/$frags->tag", function() use ($frags) {
    $this->get("/{tag}[/$frags->page/{page:[0-9]+}]", 'Leafpub\Controller\ThemeController:tag');
})->add('Leafpub\Middleware:adjustPageNumbers');

// Search
$app->group("/$frags->search", function() use($frags) {
    $this->get("[/{query}[/$frags->page/{page:[0-9]+}]]", 'Leafpub\Controller\ThemeController:search');
})->add('Leafpub\Middleware:adjustSearchQuery')->add('Leafpub\Middleware:adjustPageNumbers');

// Posts
$app->get('/{post}', 'Leafpub\Controller\ThemeController:post');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Custom handlers
////////////////////////////////////////////////////////////////////////////////////////////////////

// Not found handler
$container['notFoundHandler'] = function($container) {
    return function($request, $response) use ($container) {
        return $response->withStatus(404)->write(Error::render());
    };
};

// Not allowed handler
$container['notAllowedHandler'] = function($container) {
    return function($request, $response, $methods) use ($container) {
        return $response->withStatus(405)->write(Error::system([
            'title' => 'Method Not Allowed',
            'message' => 'Method must be one of: ' . implode(', ', $methods)
        ]));
    };
};

// Error handlers
$container['errorHandler'] = function($container) {
    return function($request, $response, $exception) use ($container) {
        return $response->withStatus(500)->write(Error::system([
            'title' => 'Application Error',
            'message' => $exception->getMessage()
        ]));
    };
};

// Run it!
$app->run();
