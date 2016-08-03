<?php
/*
Postleaf: Simple, beautiful publishing.
Copyright 2016 A Beautiful Site, LLC

Website: https://www.postleaf.org/

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
namespace Postleaf;
require __DIR__ . '/source/runtime.php';

// Initialize the app and session
Postleaf::run();
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
$app->add('Postleaf\Middleware:removeTrailingSlashes');

////////////////////////////////////////////////////////////////////////////////////////////////////
// API routes
////////////////////////////////////////////////////////////////////////////////////////////////////

// Unprotected
$app->group("/api", function() {
    // Auth
    $this->post('/login', 'Postleaf\Controller\APIController:login');
    $this->post('/login/recover', 'Postleaf\Controller\APIController:recover');
    $this->post('/login/reset', 'Postleaf\Controller\APIController:reset');
});

// Protected
$app->group("/api", function() {
    // Posts
    $this->get('/posts', 'Postleaf\Controller\APIController:getPosts');
    $this->post('/posts', 'Postleaf\Controller\APIController:addPost');
    $this->put('/posts/{slug}', 'Postleaf\Controller\APIController:updatePost');
    $this->delete('/posts/{slug}', 'Postleaf\Controller\APIController:deletePost');
    $this->get('/posts/render', 'Postleaf\Controller\APIController:renderPost');
    $this->post('/posts/render', 'Postleaf\Controller\APIController:renderPost');

    // History
    $this->get('/history/{id}', 'Postleaf\Controller\APIController:getHistory');
    $this->delete('/history/{id}', 'Postleaf\Controller\APIController:deleteHistory');

    // Tags
    $this->get('/tags', 'Postleaf\Controller\APIController:getTags');
    $this->post('/tags', 'Postleaf\Controller\APIController:addTag');
    $this->put('/tags/{slug}', 'Postleaf\Controller\APIController:updateTag');
    $this->delete('/tags/{slug}', 'Postleaf\Controller\APIController:deleteTag');

    // Navigation
    $this->put('/navigation', 'Postleaf\Controller\APIController:updateNavigation');

    // Users
    $this->get('/users', 'Postleaf\Controller\APIController:getUsers');
    $this->post('/users', 'Postleaf\Controller\APIController:addUser');
    $this->put('/users/{slug}', 'Postleaf\Controller\APIController:updateUser');
    $this->delete('/users/{slug}', 'Postleaf\Controller\APIController:deleteUser');

    // Settings
    $this->post('/settings', 'Postleaf\Controller\APIController:updateSettings');
    $this->delete('/settings/cache', 'Postleaf\Controller\APIController:deleteCache');

    // Backups
    $this->post('/backup', 'Postleaf\Controller\APIController:addBackup');
    $this->delete('/backup/{file}', 'Postleaf\Controller\APIController:deleteBackup');
    $this->get('/backup/{file}', 'Postleaf\Controller\APIController:getBackup');
    $this->post('/backup/{file}/restore', 'Postleaf\Controller\APIController:restoreBackup');

    // Locater
    $this->get('/locater', 'Postleaf\Controller\APIController:getLocater');

    // Uploads
    $this->post('/uploads', 'Postleaf\Controller\APIController:addUpload');

    // Utilities
    $this->get('/oembed', 'Postleaf\Controller\APIController:getOembed');
})->add('Postleaf\Middleware:requireAuth');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Admin views
////////////////////////////////////////////////////////////////////////////////////////////////////

// Unprotected
$app->group("/$frags->admin", function() {
    $this->get('/login', 'Postleaf\Controller\AdminController:login');
    $this->get('/login/recover', 'Postleaf\Controller\AdminController:recover');
    $this->get('/login/reset', 'Postleaf\Controller\AdminController:reset');
    $this->get('/logout', 'Postleaf\Controller\AdminController:logout');
});

// Protected
$app->group("/$frags->admin", function() {
    // Dashboard
    $this->get('', 'Postleaf\Controller\AdminController:dashboard');

    // Posts
    $this->get('/posts', 'Postleaf\Controller\AdminController:posts');
    $this->get('/posts/new', 'Postleaf\Controller\AdminController:newPost');
    $this->get('/posts/{slug}', 'Postleaf\Controller\AdminController:editPost');
    $this->get('/posts/{slug}/history/{id}', 'Postleaf\Controller\AdminController:history');

    // Tags
    $this->get('/tags', 'Postleaf\Controller\AdminController:tags');
    $this->get('/tags/new', 'Postleaf\Controller\AdminController:newTag');
    $this->get('/tags/{slug}', 'Postleaf\Controller\AdminController:editTag');

    // Navigation
    $this->get('/navigation', 'Postleaf\Controller\AdminController:navigation');

    // Users
    $this->get('/users', 'Postleaf\Controller\AdminController:users');
    $this->get('/users/new', 'Postleaf\Controller\AdminController:newUser');
    $this->get('/users/{slug}', 'Postleaf\Controller\AdminController:editUser');

    // Settings
    $this->get('/settings', 'Postleaf\Controller\AdminController:settings');
})->add('Postleaf\Middleware:requireAuth');

////////////////////////////////////////////////////////////////////////////////////////////////////
// Theme views
////////////////////////////////////////////////////////////////////////////////////////////////////

// Homepage
if(Setting::get('homepage')) {
    // Custom homepage
    $app->get('/', 'Postleaf\Controller\ThemeController:customHomepage');

    // Blog at /blog
    $app->group("/$frags->blog", function() use($frags) {
        $this->get("[/$frags->page/{page:[0-9]+}]", 'Postleaf\Controller\ThemeController:blog');
    })->add('Postleaf\Middleware:adjustPageNumbers');
} else {
    // Blog at the homepage
    $app->group('/', function() use($frags) {
        $this->get("[$frags->page/{page:[0-9]+}]", 'Postleaf\Controller\ThemeController:blog');
    })->add('Postleaf\Middleware:adjustPageNumbers');
}

// Feed
$app->get("/$frags->feed", 'Postleaf\Controller\ThemeController:feed');

// Authors
$app->group("/$frags->author", function() use ($frags) {
    $this->get("/{author}[/$frags->page/{page:[0-9]+}]", 'Postleaf\Controller\ThemeController:author');
})->add('Postleaf\Middleware:adjustPageNumbers');

// Tags
$app->group("/$frags->tag", function() use ($frags) {
    $this->get("/{tag}[/$frags->page/{page:[0-9]+}]", 'Postleaf\Controller\ThemeController:tag');
})->add('Postleaf\Middleware:adjustPageNumbers');

// Search
$app->group("/$frags->search", function() use($frags) {
    $this->get("[/{query}[/$frags->page/{page:[0-9]+}]]", 'Postleaf\Controller\ThemeController:search');
})->add('Postleaf\Middleware:adjustSearchQuery')->add('Postleaf\Middleware:adjustPageNumbers');

// Posts
$app->get('/{post}', 'Postleaf\Controller\ThemeController:post');

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
