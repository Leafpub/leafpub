<?php
namespace Leafpub;
use Leafpub\Models\Setting;

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

})->add('Leafpub\Middleware:requireAuth')->add('Leafpub\Middleware:checkDBScheme');

/**
* Admin views
**/

/** Unprotected **/
$app->group("/$frags->admin", function() {
    $this->get('/login', 'Leafpub\Controller\AdminController:login');
    $this->get('/login/recover', 'Leafpub\Controller\AdminController:recover');
    $this->get('/login/reset', 'Leafpub\Controller\AdminController:reset');
    $this->get('/logout', 'Leafpub\Controller\AdminController:logout');
});

/** Protected **/
$app->group("/$frags->admin", function() {
    // Dashboard
    $this->get('', 'Leafpub\Controller\AdminController:dashboard');

    // Importer
    $this->get('/import', 'Leafpub\Controller\AdminController:import');

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

    // Plugins
    $this->get('/plugins', 'Leafpub\Controller\AdminController:plugins');

    // Uploads
    $this->get('/uploads', 'Leafpub\Controller\AdminController:uploads');
    $this->get('/regenerateThumbnails', 'Leafpub\Controller\AdminController:regenerateThumbnails');

    $this->get('/updateLeafpub', 'Leafpub\Controller\AdminController:updateLeafpub');

})->add('Leafpub\Middleware:requireAuth')->add('Leafpub\Middleware:checkDBScheme');

/**
* Theme views
**/

/** Homepage **/
if(Setting::getOne('homepage')) {
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

$app->get('/sitemap', 'Leafpub\Controller\ThemeController:sitemap');

// Posts
$app->get('/{post}', 'Leafpub\Controller\ThemeController:post');
if (Setting::getOne('amp') == 'on'){
    $app->get('/{post}/amp', 'Leafpub\Controller\ThemeController:ampify');
}
?>