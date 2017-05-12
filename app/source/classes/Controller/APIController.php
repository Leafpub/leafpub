<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
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
    Leafpub\Update,
    Leafpub\Widget,
    Leafpub\Mailer\Mail\MailFactory,
    Leafpub\Mailer\Mail\AddressFactory,
    Leafpub\Events\Application\MailCompose,
    Leafpub\Events\Application\MailSend,
    Leafpub\Models\History,
    Leafpub\Models\Post,
    Leafpub\Models\Setting,
    Leafpub\Models\Tag,
    Leafpub\Models\Upload,
    Leafpub\Models\User,
    Leafpub\Models\Plugin;
    

/**
* APIController
*
* This class handles all ajax requests from the Leafpub backend.
* It's the controller for API endpoints
*
* @package Leafpub\Controller
**/
class APIController extends Controller {

    /**
    * Handles POST api/login
    *
    * @param \Slim\Http\Request $request 
    * @param \Slim\Http\Response $response 
    * @param array $args 
    * @return \Slim\Http\Response (json)
    *
    **/
    public function login($request, $response, $args) {
        $params = $request->getParams();

        if(Session::login($params['username'], $params['password'])) {
            return $response->withJson([
                'success' => true
            ]);
        } else {
            return $response->withJson([
                'success' => false,
                'invalid' => ['username', 'password'],
                'message' => Language::term('invalid_username_or_password')
            ]);
        }
    }

    /**
    * Handles POST api/login/recover
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function recover($request, $response, $args) {
        $params = $request->getParams();

        // Get the user
        $user = User::getOne($params['username']);
        if(!$user) {
            return $response->withJson([
                'success' => false,
                'invalid' => ['username'],
                'message' => Language::term('invalid_username')
            ]);
        }

        // Generate and set a password reset token
        User::edit($user['slug'], [
            'reset_token' => $token = Leafpub::randomBytes(50)
        ]);

        // Send the user an email
        try {
            $emailData = MailFactory::create([
                'to' => AddressFactory::create($user['email']),
                'subject' => '[' . Setting::getOne('title') . '] ' . Language::term('password_reset'),
                'message' =>
                    Language::term('a_password_reset_has_been_requested_for_this_account') . "\n\n" .
                    $user['name'] . ' — ' . $user['slug'] . "\n\n" .
                    Language::term('if_this_was_sent_in_error_you_can_ignore_this_message') . "\n\n" .
                    Language::term('to_reset_your_password_visit_this_address') . ' ' .
                    Admin::url('login/reset/?username=' . rawurlencode($user['slug']) .
                               '&token=' . rawurlencode($token)),
                'from' => AddressFactory::create('leafpub@' . $_SERVER['HTTP_HOST'], 'Leafpub')
            ]);

            $evt = new MailCompose($emailData);
            Leafpub::dispatchEvent(MailCompose::NAME, $evt);
            $emailData = $evt->getEventData();

            $evt = new MailSend($emailData);
            Leafpub::dispatchEvent(MailSend::NAME, $evt);

            Mailer::sendEmail($emailData);
        } catch (MailerException $error) {
            return $response->withJson([
                'success' => false,
                'message' => $error->getMessage()
            ]);
        }

        // Send response
        return $response->withJson([
            'success' => true,
            'message' => Language::term('check_your_email_for_further_instructions')
        ]);
    }

    /**
    * Handles POST api/login/reset
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function reset($request, $response, $args) {
        $params = $request->getParams();

        // Get the user
        $user = User::getOne($params['username']);
        if(!$user) {
            return $response->withJson([
                'success' => false,
                'message' => Language::term('invalid_username')
            ]);
        }

        // Tokens must match and cannot be empty
        if(empty($params['token']) || $params['token'] !== $user['reset_token']) {
            return $response->withJson([
                'success' => false,
                'message' => Language::term('invalid_or_expired_token')
            ]);
        }

        // New passwords must match
        if($params['password'] !== $params['verify-password']) {
            return $response->withJson([
                'status' => 'error',
                'invalid' => ['password', 'verify-password'],
                'message' => Language::term('the_passwords_you_entered_do_not_match')
            ]);
        }

        // Change password and remove token
        try {
            User::edit($user['slug'], [
                'password' => $params['password'],
                'reset_token' => ''
            ]);
        } catch(Exception $e) {
            return $response->withJson([
                'success' => false,
                'invalid' => ['password'],
                'message' => Language::term('the_password_you_entered_is_too_short')
            ]);
        }

        // Log the user in for convenience
        Session::login($user['slug'], $params['password']);

        // Send response
        return $response->withJson([
            'success' => true
        ]);
    }

    /**
    * Handles GET api/posts
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function getPosts($request, $response, $args) {
        $params = $request->getParams();

        // Get posts
        $posts = Post::getMany([
            // If you're not an owner, admin, or editor then you can only see your own posts
            'author' => Session::isRole(['owner', 'admin', 'editor']) ? null : Session::user('slug'),
            'status' => null,
            'ignore_featured' => false,
            'ignore_pages' => false,
            'ignore_sticky' => false,
            'items_per_page' => 50,
            'end_date' => null,
            'page' => (int) $params['page'],
            'query' => empty($params['query']) ? null : $params['query']
        ], $pagination);

        if ($this->returnJson($request)){
            $postArray = [
                'success' => true,
                'posts' => $posts,
                'pagination' => $pagination
            ];
        } else {
            // Render post list
            $html = Admin::render('partials/post-list', [
                'posts' => $posts
            ]);

            $postArray = [
                'success' => true,
                'html' => $html,
                'pagination' => $pagination
            ];
        }

        // Send response
        return $response->withJson($postArray);
    }

    /**
    * Handles adding and updating of a post
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    private function addUpdatePost($action, $request, $response, $args) {
        $params = $request->getParams();
        $properties = $params['properties'];
        $slug = $action === 'add' ? $properties['slug'] : $args['slug'];
        
        if ($slug !== $properties['slug']){
            $properties['oldSlug'] = $slug;
        } else {
            $properties['slug'] = $slug;
        }

        if ($properties['author'] === '=quick='){
            $properties['author'] = Session::user('slug');
        }

        // If you're not an owner, admin, or editor then you can only add/update your own posts
        if(
            !Session::isRole(['owner', 'admin', 'editor']) &&
            $properties['author'] != Session::user('slug')
        ) {
            return $response->withStatus(403);
        }

        // To edit a post, you must be an owner, admin, editor or the owner of the post
        if(
            $action === 'update' &&
            !Session::isRole(['owner', 'admin', 'editor']) &&
            Post::getOne($params['post'])['author'] !== Session::user('slug')
        ) {
            return $response->withStatus(403);
        }

        // To feature a post, turn it into a page, or make it sticky you must be owner, admin, or
        // editor
        if(!Session::isRole(['owner', 'admin', 'editor'])) {
            unset($properties['featured']);
            unset($properties['page']);
            unset($properties['sticky']);
        }

        // Create tags that don't exist yet
        if(Session::isRole(['owner', 'admin', 'editor'])) {
            foreach((array) $properties['tag_data'] as $tag) {
                if(!Tag::exists($tag['slug'])) {
                    Tag::create([
                        'slug' => $tag['slug'],
                        'name' => $tag['name'],
                        'type' => 'post'
                    ]);
                }
            }
        }

        // Update the post
        try {
            if($action === 'add') {
                Post::create($properties);
            } else {
                Post::edit($properties);
            }
        } catch(\Exception $e) {
            switch($e->getCode()) {
                case Post::INVALID_SLUG:
                    $message = Language::term('the_slug_you_entered_cannot_be_used');
                    $invalid = ['slug'];
                    break;
                case Post::ALREADY_EXISTS:
                    $message = Language::term('a_post_already_exists_at_the_url_you_entered');
                    $invalid = ['slug'];
                    break;
                case Post::INVALID_USER:
                    $message = Language::term('invalid_username');
                    $invalid = ['author'];
                    break;
                default:
                    $message = $e->getMessage();
            }
            return $response->withJson([
                'success' => false,
                'message' => $message,
                'invalid' => $invalid
            ]);
        }

        // Send response
        return $response->withJson([
            'success' => true
        ]);
    }

    /**
    * Handles POSTS api/posts
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function addPost($request, $response, $args) {
        return $this->addUpdatePost('add', $request, $response, $args);
    }

    /**
    * Handles PUT api/posts/{slug}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function updatePost($request, $response, $args) {
        return $this->addUpdatePost('update', $request, $response, $args);
    }

    /**
    * Handles DELETE api/posts/{slug}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function deletePost($request, $response, $args) {

        // If you're not an owner, admin, or editor then you can only delete your own posts
        if(
            Session::isRole(['owner', 'admin', 'editor']) ||
            Post::getOne($args['slug'])['author'] === Session::user('slug')
        ) {
            return $response->withJson([
                'success' => Post::delete($args['slug'])
            ]);
        }

        return $response->withJson([
            'success' => false
        ]);
    }

    /**
    * Handles GET/POST api/posts/render
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function renderPost($request, $response, $args) {
        //
        // Render an editable post for the editor. This method supports GET and POST due to query
        // string size limitations.
        //
        //  Post data can be submitted one of three ways:
        //
        //      $request['post'] = 'post-slug'
        //      $request['post'] = ['title' => $title, 'content' => $content, ...]
        //      $request['post-json'] = '<json string>';
        //
        // Tags should be posted as `tags`:
        //
        //      ['favorites', 'life', 'love']
        //
        // Tag data should be passed in as `tag_data`. This data will be used to render previews for tags
        // that haven't been created yet.
        //
        //      [
        //          ['slug' => 'tag-1', 'name' => 'Tag 1'],
        //          ['slug' => 'tag-2', 'name' => 'Tag 2'],
        //          ...
        //      ]
        //
        $params = $request->getParams();
        
        // Generate post data
        if(isset($params['post-json'])) {
            // Post was passed as JSON data
            $post = json_decode($params['post-json'], true);
        } else {
            if(isset($params['new'])) {
                // New post
                $post = [
                    'slug' => ':new',
                    'title' => Setting::getOne('default_title'),
                    'content' => Leafpub::markdownToHtml(Setting::getOne('default_content')),
                    'author' => Session::user(),
                    'pub_date' => date('Y-m-d H:i:s')
                ];
            } else {
                // Existing post
                $post = $params['post'];
            }
        }

        // Render it
        try {
            $html = Post::render($post, [
                'editable' => true,
                'preview' => true,
                'zen' => $params['zen'] === 'true'
            ]);
        } catch(\Exception $e) {
            return $response
                ->withStatus(500)
                ->write(
                    Error::system([
                        'title' => 'Application Error',
                        'message' => $e->getMessage()
                    ])
                );
        }

        return $response
            // Prevents `Refused to execute a JavaScript script` error
            ->withAddedHeader('X-XSS-Protection', '0')
            ->write($html);
    }

    public function unlockPost($request, $response, $args){
        $post = Post::getOne($args['slug']);
        if($post['meta']['lock'][0] === Session::user('slug')) {
            return $response->withJson([
                'success' => Post::unlockPostAfterEdit($post['id'])
            ]);
        }

        return $response->withJson(['success' => false]);
    }

    public function activatePlugin($request, $response, $args){
        if (!Session::isRole(['owner', 'admin'])){
            return $response->withJson([
                'success' => false
            ]);
        } else {
            $params = $request->getParams();
            
            $plugin = $params['plugin'];
            $enable = ($params['enable'] === 'false' ? true : false);
            if ($enable){
                $ret = Plugin::activate($plugin);
            } else {
                $ret = Plugin::deactivate($plugin);
            }
            return $response->withJson([
                'success' => $ret
            ]);
        }
    }

    public function deletePlugin($request, $response, $args){
         if (!Session::isRole(['owner', 'admin'])){
            return $response->withJson([
                'success' => false
            ]);
        } else {
            $dir = $args['plugin'];
            $plugin = Plugin::getOne($dir);

            if (!$plugin){
                return $response->withJson([
                    'success' => false
                ]);
            }

            $ret = Plugin::deinstall($dir);
            
            return $response->withJson([
                'success' => $ret
            ]);
        }
    }

    public function uploadPlugin($request, $response, $args){
        // Loop through uploaded files
        foreach($request->getUploadedFiles()['files'] as $upload) {
            $extension = Leafpub::fileExtension($upload->getClientFilename());

            // Check for a successful upload
            if($upload->getError() !== UPLOAD_ERR_OK) {
                return $response->withJson([
                    'success' => false,
                    'message' => Language::term('upload_failed')
                ]);
            }

            if ($extension === 'zip'){
                try {
                    // Add the file to uploads
                    Plugin::install($upload->file);
                } catch(\Exception $e) {
                    return $response->withJson([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
            } else {
                return $response->withJson([
                    'success' => false,
                    'message' => 'WRONG_EXTENSION'
                ]);
            }
        }

        return $response->withJson([
            'success' => true
        ]);
    }

    /**
    * Handles GET api/plugins
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function getPlugins($request, $response, $args) {
        $params = $request->getParams();

        // To view users, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }

        // Get users
        $plugins = Plugin::getMany([
            'items_per_page' => 10,
            'page' => (int) $params['page'],
            'query' => empty($params['query']) ? null : $params['query']
        ], $pagination);

        if ($this->returnJson($request)){
            $pluginArray = [
                'success' => true,
                'plugins' => $plugins,
                'pagination' => $pagination
            ];
        } else {
            // Render post list
            $html = Admin::render('partials/plugin-list', [
                'plugins' => $plugins
            ]);

            $pluginArray = [
                'success' => true,
                'html' => $html,
                'pagination' => $pagination
            ];
        }

        // Send response
        return $response->withJson($pluginArray);
    }

    /**
    * Handles DELETE api/history{id} 
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function deleteHistory($request, $response, $args) {
        // Get the history item and the affected post so we can verify privileges
        $history = History::getOne($args['id']);
        $post = Post::getOne((int) $history['post']);
        if(!$history || !$post) {
            return $response->withJson([
                'success' => false
            ]);
        }

        // If you're not an owner, admin, or editor then you can only delete history that belongs to
        // your own own post
        if(
            Session::isRole(['owner', 'admin', 'editor']) ||
            $post['author'] === Session::user('slug')
        ) {
            return $response->withJson([
                'success' => History::delete($args['id'])
            ]);
        }

        return $response->withJson([
            'success' => false
        ]);
    }

    /**
    * Handles GET api/history/{id}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function getHistory($request, $response, $args) {
        $history = History::getOne($args['id']);
        if(!$history) {
            return $response->withJson([
                'success' => false
            ]);
        }

        // Provide a neatly formed pub_date and pub_time for the editor
        $history['post_data']['pub_time'] =
            Leafpub::strftime('%H:%M', strtotime($history['post_data']['pub_date']));

        $history['post_data']['pub_date'] =
            // HTML5 input type=date needs format Y-m-d
            Leafpub::strftime('%Y-%m-%d', strtotime($history['post_data']['pub_date']));

        // Return the requested history item
        return $response->withJson([
            'success' => true,
            'history' => $history
        ]);
    }

    /**
    * Handles GET api/tags
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function getTags($request, $response, $args) {
        $params = $request->getParams();

        // To view tags, you must be an owner, admin, or editor
        if(!Session::isRole(['owner', 'admin', 'editor'])) {
            return $response->with(403);
        }

        // Get tags
        $tags = Tag::getMany([
            'items_per_page' => 50,
            'page' => (int) $params['page'],
            'query' => empty($params['query']) ? null : $params['query']
        ], $pagination);

        if ($this->returnJson($request)){
            $tagArray = [
                'success' => true,
                'tags' => $tags,
                'pagination' => $pagination
            ];
        } else {
            // Render tag list
            $html = Admin::render('partials/tag-list', [
                'tags' => $tags
            ]);

            $tagArray = [
                'success' => true,
                'html' => $html,
                'pagination' => $pagination
            ];
        }

        // Send response
        return $response->withJson($tagArray);
    }

    /**
    * Private method to handle add and update
    *
    * @param String $action
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    private function addUpdateTag($action, $request, $response, $args) {
        $params = $request->getParams();
        $slug = $action === 'add' ? $params['slug'] : $args['slug'];

        // To add/update tags, you must be an owner, admin, or editor
        if(!Session::isRole(['owner', 'admin', 'editor'])) {
            return $response->with(403);
        }

        // Create tag array
        $tag = [
            'slug' => $slug,
            'name' => $params['name'],
            'description' => $params['description'],
            'meta_title' => $params['meta-title'],
            'meta_description' => $params['meta-description'],
            'cover' => $params['cover'],
            'type' => $params['type']
        ];

        // Add/update the tag
        try {
            if($action === 'add') {
                Tag::create($tag);
            } else {
                Tag::edit($tag);
            }
        } catch(\Exception $e) {
            // Handle errors
            switch($e->getCode()) {
                case Tag::INVALID_SLUG:
                    $invalid = ['slug'];
                    $message = Language::term('the_slug_you_entered_cannot_be_used');
                    break;
                case Tag::ALREADY_EXISTS:
                    $invalid = ['slug'];
                    $message = Language::term('a_tag_already_exists_at_the_slug_you_entered');
                    break;
                case Tag::INVALID_NAME:
                    $invalid = ['name'];
                    $message = Language::term('the_name_you_entered_cannot_be_used');
                    break;
                default:
                    $message = $e->getMessage();
            }

            return $response->withJson([
                'success' => false,
                'invalid' => $invalid,
                'message' => $message
            ]);
        }

        return $response->withJson([
            'success' => true
        ]);
    }

    /**
    * Handles POST api/tags
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function addTag($request, $response, $args) {
        return $this->addUpdateTag('add', $request, $response, $args);
    }

    /**
    * Handles PUT  api/tags/{slug}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function updateTag($request, $response, $args) {
        return $this->addUpdateTag('update', $request, $response, $args);
    }

    /**
    * Handles DELETE api/tags/{slug}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function deleteTag($request, $response, $args) {
        // To delete tags, you must be an owner, admin, or editor
        if(!Session::isRole(['owner', 'admin', 'editor'])) {
            return $response->with(403);
        }
        
        $ret = Tag::delete($args['slug']);
       
        return $response->withJson([
            'success' => $ret
        ]);
    }

    /**
    * Handles PUT api/navigation
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function updateNavigation($request, $response, $args) {
        $params = $request->getParams();

        // To update navigation, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }

        // Create navigation array
        $navigation = [];
        $labels = (array) $params['label'];
        $links = (array) $params['link'];
        for($i = 0; $i < count($labels); $i++) {
            // Ignore items with empty labels
            if(mb_strlen($labels[$i])) {
                $navigation[] = [
                    'label' => $labels[$i],
                    'link' => $links[$i]
                ];
            }
        }

        // Update navigation in settings
        Setting::edit(['name' =>'navigation', 'value' => json_encode($navigation)]);

        // Send response
        return $response->withJson([
            'success' => true
        ]);
    }

    /**
    * Handles GET api/users
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function getUsers($request, $response, $args) {
        $params = $request->getParams();

        // To view users, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }

        // Get users
        $users = User::getMany([
            'items_per_page' => 50,
            'page' => (int) $params['page'],
            'query' => empty($params['query']) ? null : $params['query']
        ], $pagination);

        if ($this->returnJson($request)){
            $userArray = [
                'success' => true,
                'user' => $users,
                'pagination' => $pagination
            ];
        } else {
            // Render post list
            $html = Admin::render('partials/user-list', [
                'users' => $users
            ]);

            // Send response
            $userArray = [
                'success' => true,
                'html' => $html,
                'pagination' => $pagination
            ];
        }

        return $response->withJson($userArray);
    }

    /**
    * Private method to handle add and update
    *
    * @param String $action
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    private function addUpdateUser($action, $request, $response, $args) {
        $params = $request->getParams();
        $slug = $action === 'add' ? $params['username'] : $args['slug'];

        // To add/update a user, you must be an owner or admin. Users are also allowed to update
        // their own profiles.
        if(!Session::isRole(['owner', 'admin']) && $slug !== Session::user('slug')) {
            return $response->withStatus(403);
        }

        // Make sure passwords match
        if($params['password'] !== $params['verify-password']) {
            return $response->withJson([
                'success' => false,
                'invalid' => ['password', 'verify-password'],
                'message' => Language::term('the_passwords_you_entered_do_not_match')
            ]);
        }

        // Create user array
        $user = [
            'slug' => $slug,
            'name' => $params['name'],
            'email' => $params['email'],
            'role' => $params['role'],
            'password' => empty($params['password']) ? null : $params['password'],
            'twitter' => $params['twitter'],
            'website' => $params['website'],
            'location' => $params['location'],
            'bio' => $params['bio'],
            'avatar' => $params['avatar'],
            'cover' => $params['cover']
        ];

        // Add/update the user
        try {
            if($action === 'add') {
                User::create($user);
            } else {
                User::edit($user);
            }
        } catch(\Exception $e) {
            // Handle errors
            switch($e->getCode()) {
                case User::CANNOT_CHANGE_OWNER:
                    $invalid = ['role'];
                    $message = Language::term('the_owner_role_cannot_be_revoked_or_reassigned');
                    break;
                case User::INVALID_SLUG:
                    $invalid = ['username'];
                    $message = Language::term('the_username_you_entered_cannot_be_used');
                    break;
                case User::ALREADY_EXISTS:
                    $invalid = ['username'];
                    $message = Language::term('the_username_you_entered_is_already_taken');
                    break;
                case User::INVALID_NAME:
                    $invalid = ['name'];
                    $message = Language::term('the_name_you_entered_cannot_be_used');
                    break;
                case User::INVALID_EMAIL:
                    $invalid = ['email'];
                    $message = Language::term('the_email_address_you_entered_is_not_valid');
                    break;
                case User::PASSWORD_TOO_SHORT:
                    $invalid = ['password', 'verify-password'];
                    $message = Language::term('the_password_you_entered_is_too_short');
                    break;
                case User::INVALID_PASSWORD:
                    $invalid = ['password', 'verify-password'];
                    $message = Language::term('the_password_you_entered_is_not_valid');
                    break;
                default:
                    $message = $e->getMessage();
            }

            return $response->withJson([
                'success' => false,
                'invalid' => $invalid,
                'message' => $message
            ]);
        }

        return $response->withJson([
            'success' => true
        ]);
    }

    /**
    * Handles POST api/users
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function addUser($request, $response, $args) {
        return $this->addUpdateUser('add', $request, $response, $args);
    }

    /**
    * Handles PUT api/users/{slug}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function updateUser($request, $response, $args) {
        return $this->addUpdateUser('update', $request, $response, $args);
    }

    /**
    * Handles DELETE api/users/{slug}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function deleteUser($request, $response, $args) {
        // To delete a user, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }
        
        //TODO: Add recipient!!
        // Delete the user
        try {
            User::delete([
                'slug' => $args['slug'],
                'recipient' => null    
            ]);

            // Did you delete yourself? If so, cya!
            if($args['slug'] === Session::user()['slug']) Session::logout();
        } catch(\Exception $e) {
            if($e->getCode() === User::CANNOT_DELETE_OWNER) {
                return $response->withJson([
                    'success' => false,
                    'message' => Language::term('the_owner_account_cannot_be_deleted')
                ]);
            }
        }
        return $response->withJson([
            'success' => true
        ]);
    }

    /**
    * Handles POST api/settings/update
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function updateSettings($request, $response, $args) {
        $params = $request->getParams();

        // To edit settings, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }

        // Create settings array
        $settings = [
            'title' => $params['title'],
            'tagline' => $params['tagline'],
            'homepage' => $params['homepage'],
            'twitter' => $params['twitter'],
            'theme' => $params['theme'],
            'posts_per_page' => $params['posts-per-page'],
            'cover' => $params['cover'],
            'logo' => $params['logo'],
            'favicon' => $params['favicon'],
            'generator' => $params['generator'] === 'on' ? 'on' : 'off',
            'default_title' => $params['default-title'],
            'default_content' => $params['default-content'],
            'language' => $params['language'],
            'timezone' => $params['timezone'],
            'head_code' => $params['head-code'],
            'foot_code' => $params['foot-code'],
            'maintenance' => $params['maintenance'] === 'on' ? 'on' : 'off',
            'maintenance_message' => $params['maintenance-message'],
            'hbs_cache' => $params['hbs-cache'] === 'on' ? 'on' : 'off',
            'mailer' => $params['mailer'],
            'showDashboard' => $params['showDashboard'] === 'on' ? 'on' : 'off',
            'amp' => $params['amp'] === 'on' ? 'on' : 'off'
        ];

        // Update settings
        foreach($settings as $name => $value) {
            Setting::edit(['name' => $name, 'value' => $value]);
        }

        if (!Language::installLanguage($params['language'])){
            Setting::edit(['name' => 'language', 'value' => 'en-us']);
            return $response->withJson([
                'success' => false,
                'message' => 'Unable to download and install ' . $params['language'] . '. Setting language back to en-us.'
            ]);
        }

        // Send response
        return $response->withJson([
            'success' => true
        ]);
    }

    /**
    * Handles DELETE api/settings/cache
    *
    * @param $request
    * @param $response
    * @param $args
    * @return \Slim\Http\Response
    *
    **/
    public function deleteCache($request, $response, $args) {
        Cache::flush();

        // Send response
        return $response->withJson([
            'success' => true,
            'message' => Language::term('cache_has_been_cleared')
        ]);
    }

    /**
    * Returns the HTML for the backups table
    *
    * @return mixed
    **/
    private function getBackupTableHTML() {
        // To manage backups, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }

        return Renderer::render([
            'template' => Leafpub::path('source/templates/partials/backups-table.hbs'),
            'data' => [
                'backups' => Backup::getAll()
            ],
            'helpers' => ['admin', 'url', 'utility']
        ]);
    }

    /**
    * Handles POST api/backup
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function addBackup($request, $response, $args) {
        // To manage backups, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }

        try {
            $backup = Backup::create();
        } catch(\Exception $e) {
            switch($e->getCode()) {
                case Backup::UNABLE_TO_CREATE_DIRECTORY:
                    $message = Language::term('unable_to_create_directory_{dir}', [
                        'dir' => Leafpub::path('backups')
                    ]);
                    break;
                case Backup::UNABLE_TO_BACKUP_DATABASE:
                    $message = Language::term('unable_to_backup_database');
                    break;
                case Backup::UNABLE_TO_CREATE_ARCHIVE:
                    $message = Language::term('unable_to_create_backup_file');
                    break;
                default:
                    $message = $e->getMessage();
            }

            return $response->withJson([
                'success' => false,
                'message' => $message
            ]);
        }

        // Send response
        return $response->withJson([
            'success' => true,
            'html' => self::getBackupTableHTML()
        ]);
    }

    /**
    * Handles DELETE api/backup/{file}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function deleteBackup($request, $response, $args) {
        // To manage backups, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }

        Backup::delete($args['file']);

        // Send response
        return $response->withJson([
            'success' => true,
            'html' => self::getBackupTableHTML()
        ]);
    }

    /**
    * Handles GET api/backup/{file}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function getBackup($request, $response, $args) {
        // To manage backups, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }

        // Get backup
        $backup = Backup::get($args['file']);
        if(!$backup) {
            return $this->notFound($request, $response);
        }

        // Stream the file
        $stream = new \Slim\Http\Stream(fopen($backup['pathname'], 'rb'));
        return $response
            ->withHeader('Content-Type', 'application/force-download')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Type', 'application/download')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $backup['filename'] . '"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->withHeader('Pragma', 'public')
            ->withBody($stream);
    }

    /**
    * Handles POST api/backup/{file}/restore
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function restoreBackup($request, $response, $args) {
        $params = $request->getParams();

        // To manage backups, you must be an owner or admin
        if(!Session::isRole(['owner', 'admin'])) {
            return $response->withStatus(403);
        }

        // Verify password
        if(!User::verifyPassword(Session::user('slug'), $params['password'])) {
            return $response->withJson([
                'success' => false,
                'message' => Language::term('your_password_is_incorrect')
            ]);
        }

        // Restore the backup
        try {
            Backup::restore($args['file']);
        } catch(\Exception $e) {
            return $response->withJson([
                'success' => false,
                'message' => Language::term('unable_to_restore_backup') . ' – ' . $e->getMessage()
            ]);
        }

        // Send response
        return $response->withJson([
            'success' => true,
            'message' => Language::term('the_backup_has_been_restored')
        ]);
    }

    /**
    * Handles GET api/locater
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function getLocater($request, $response, $args) {
        $params = $request->getParams();
        $html = '';
        $items = [];
        $isDashboard = (substr($request->getServerParam('HTTP_REFERER'), -5) == 'admin');

        if ($isDashboard){
            foreach(Widget::getWidgets() as $widget){
                if(mb_stristr($widget['name'], $params['query'])){
                    $items[] = [
                        'title' => 'Widget: ' . $widget['name'],
                        'name' => $widget['name'],
                        'description' => $widget['description'],
                        'link' => '#',
                        'icon' => 'fa fa-rocket'
                    ];
                }
            }
        }

        // Search menu items
        foreach(Admin::getMenuItems() as $nav) {
            if(mb_stristr($nav['title'], $params['query'])) {
                $items[] = [
                    'title' => $nav['title'],
                    'description' => null,
                    'link' => $nav['link'],
                    'avatar' => $nav['avatar'],
                    'icon' => $nav['icon']
                ];
            }
        }

        // Search posts
        $posts = Post::getMany([
            // If you're not an owner, admin, or editor then you can only see your own posts
            'author' => Session::isRole(['owner', 'admin', 'editor']) ? null : Session::user('slug'),
            'end_date' => null,
            'status' => null,
            'ignore_featured' => false,
            'ignore_pages' => false,
            'ignore_sticky' => false,
            'ignore_posts' => false,
            'items_per_page' => 5,
            'page' => 1,
            'query' => $params['query'],
            'start_date' => null,
            'tag' => null
        ]);
        foreach($posts as $post) {
            $items[] = [
                'title' => $post['title'],
                'description' => $post['slug'],
                'link' => Admin::url('posts/' . rawurlencode($post['slug'])),
                'icon' => 'fa fa-file-text'
            ];
        }

        // Search tags
        if(Session::isRole(['owner', 'admin', 'editor'])) {
            $tags = Tag::getMany([
                'query' => $params['query'],
                'page' => 1,
                'items_per_page' => 10
            ]);
            foreach($tags as $tag) {
                $items[] = [
                    'title' => $tag['name'],
                    'description' => $tag['slug'],
                    'link' => Admin::url('tags/' . rawurlencode($tag['slug'])),
                    'icon' => 'fa fa-tag'
                ];
            }
        }

        // Search users
        if(Session::isRole(['owner', 'admin'])) {
            $users = User::getMany([
                'query' => $params['query'],
                'page' => 1,
                'items_per_page' => 10
            ]);
            foreach($users as $user) {
                $items[] = [
                    'title' => $user['name'],
                    'description' => $user['slug'],
                    'link' => Admin::url('users/' . rawurlencode($user['slug'])),
                    'icon' => 'fa fa-user',
                    'avatar' => $user['avatar']
                ];
            }
        }

        // Render it
        try {
            $html = Renderer::render([
                'template' => Leafpub::path('source/templates/partials/locater-items.hbs'),
                'helpers' => ['admin', 'url', 'utility'],
                'data' => [
                    'items' => $items
                ]
            ]);
        } catch(\Exception $e) {
            $html = '';
        }

        // Send response
        return $response->withJson([
            'success' => true,
            'html' => $html
        ]);
    }

    public function getUploads($request, $response, $args){
        $params = $request->getParams();

        // Get uploads
        $uploads = Upload::getMany([
            'items_per_page' => 20,
            'page' => (int) $params['page'],
            'query' => empty($params['query']) ? null : $params['query']
        ], $pagination);

        if ($this->returnJson($request)){
            $uploadArray = [
                'success' => true,
                'uploads' => $uploads,
                'pagination' => $pagination
            ];
        } else {
            // Render post list
            $html = Admin::render('partials/media-list', [
                'uploads' => $uploads
            ]);

            // Send response
            $uploadArray = [
                'success' => true,
                'html' => $html,
                'pagination' => $pagination
            ];
        }

        return $response->withJson($uploadArray);
    }

    public function getUpload($request, $response, $args){
        $filename = $args['file'];

        if(!$filename){
            return $response->withStatus(404);
        }

        $data = Upload::getOne($filename);

        return $response->withJson([
            'success' => true,
            'file' => $data
        ]);
    }

    /**
    * Handles POST api/upload
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function addUpload($request, $response, $args) {
        $params = $request->getParams();
        $uploaded = [];
        $failed = [];

        // Loop through uploaded files
        foreach($request->getUploadedFiles()['files'] as $upload) {
            $extension = Leafpub::fileExtension($upload->getClientFilename());

            // Check for a successful upload
            if($upload->getError() !== UPLOAD_ERR_OK) {
                $failed[] = [
                    'filename' => $upload->getClientFilename(),
                    'message' => Language::term('upload_failed')
                ];
                continue;
            }

            // Accept only images?
            if($params['accept'] === 'image') {
                try {
                    // Make sure it's really an image
                    switch($extension) {
                        case 'svg':
                            // Do nothing
                            break;
                        default:
                            if(!getimagesize($upload->file)) {
                                throw new \Exception('Invalid image format');
                            }
                            break;
                    }
                   
                    // Make it a thumbnail?
                    if(isset($params['image'])) {
                        // Decode JSON string
                        $params['image'] = json_decode($params['image']);

                        // Make the image a thumbnail
                        if(isset($params['image']['thumbnail'])) {
                            $image = new \claviska\SimpleImage($upload->file);
                            $image->thumbnail(
                                $params['image']['thumbnail']->width,
                                $params['image']['thumbnail']->height
                            )->save();
                        }
                    }
                } catch(\Exception $e) {
                    $failed[] = [
                        'filename' => $upload->getClientFilename(),
                        'message' => Language::term('invalid_image_format')
                    ];
                    continue;
                }
            }

            try {
                // Add the file to uploads
                $id = Upload::create([
                    $upload->getClientFilename(),
                    file_get_contents($upload->file),
                    &$info]
                );
                $uploaded[] = $info;
            } catch(\Exception $e) {
                $failed[] = [
                    'filename' => $upload->getClientFilename(),
                    'message' =>  Language::term('unable_to_upload_file')
                ];
            }
        }

        return $response->withJson([
            'success' => true,
            'uploaded' => $uploaded,
            'failed' => $failed
        ]);
    }

    public function editUpload($request, $response, $args){
        if (!isset($args['file'])){
            return $response->NotFound($request, $response);
        }

        $file = $args['file'];
        $params = $request->getParams();
        $params['filename'] = $file;

         // Create tags that don't exist yet
        if(Session::isRole(['owner', 'admin', 'editor'])) {
            foreach((array) $params['tagData'] as $tag) {
                if(!Tag::exists($tag['slug'])) {
                    Tag::create([
                        'slug' => $tag['slug'],
                        'name' => $tag['name'],
                        'type' => 'upload'
                    ]);
                }
            }
        }

        Upload::edit($params);

        return $response->withJson([
            'success' => true,
            'message' => Language::term('file_edited_successfully')
        ]);
    }

    public function deleteUpload($request, $response, $args){
        if (!isset($args['file'])){
            return $response->NotFound($request, $response);
        }

        $file = $args['file'];

        Upload::delete($file);
        
        return $response->withJson([
            'success' => true,
            'message' => Language::term('file_deleted_successfully')
        ]);
    }

    /**
    * Handles GET api/oembed
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return \Slim\Http\Response
    *
    **/
    public function getOembed($request, $response, $args) {
        $params = $request->getParams();

        // Fetch the embed code from the provider
        $embera = new \Embera\Embera();
        $code = $embera->autoEmbed($params['url']);
        if($embera->hasErrors() || $code === $params['url']) {
            return $response->withJson([
                'success' => false
            ]);
        }

        return $response->withJson([
            'success' => true,
            'code' => $code
        ]);
    }

    public function handleImportUpload($request, $response, $args){
        $params = $request->getParams();
        $uploaded = [];
        $failed = [];

        foreach($request->getUploadedFiles()['files'] as $upload) {
            $extension = Leafpub::fileExtension($upload->getClientFilename());

            // Check for a successful upload
            if($upload->getError() !== UPLOAD_ERR_OK) {
                $failed[] = [
                    'filename' => $upload->getClientFilename(),
                    'message' => Language::term('upload_failed')
                ];
                continue;
            }
            $upload->moveTo(Leafpub::path('content/uploads/import.xml'));
            $uploaded[] = Leafpub::path('content/uploads/import.xml');
        }

        return $response->withJson([
            'success' => true,
            'uploaded' => $uploaded,
            'failed' => $failed
        ]);
    }

    /**
    * Imports a blog. At the moment the dropins Wordpress and Ghost are available.
    * 
    * Steps:
    * 1. Set site in maintenance mode.
    * 2. Flush __posts & __post_tags
    * 3. ini_set(time_limit)
    * 4. Begin Transaction!
    * 5. Import 
    * 6. Load the pictures
    * 7. Commit Transaction.
    * 8. Delete import.xml ($file)
    * 9. Set maintenance mode off
    * 10. Done
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param array $args
    * @return bool
    * @throws \Exception
    *
    **/
    public function doImport($request, $response, $args){
        $params = $request->getParams();
        $ret = Importer::doImport($params);
        
        return $response->withJson([
            'success' => (count($ret['succeed']) > 0),
            'uploaded' => $ret['succeed'],
            'failed' => $ret['failed']
        ]);
    }

    public function updateLeafpubDatabase($request, $response, $args){
        if (!Session::isRole(['owner', 'admin'])){
            return $response->withStatus(403);
        }
        
        if (version_compare(LEAFPUB_SCHEME_VERSION, (\Leafpub\Models\Setting::getOne('schemeVersion') ?: 0)) < 1){
           return $response->withStatus(403); 
        }

        try {
            \Leafpub\Database::updateDatabase();
            \Leafpub\Leafpub::getLogger()->info('setting new scheme version');
            \Leafpub\Models\Setting::edit(['name' => 'schemeVersion', 'value' => LEAFPUB_SCHEME_VERSION]);
        } catch (\Exception $e){
            
        }

        return $response->withJson([
            'success' => true,
            'newScheme' => LEAFPUB_SCHEME_VERSION
        ]);
    }

    public function setDashboard($request, $response, $args){
        try {
            $data = $request->getParams('data');
            Leafpub::getLogger()->debug($data['data']);
            \Leafpub\Models\Setting::edit(
                [
                    'name' => 'dashboard_' . Session::user('slug'),
                    'value' => $data['data']
                ]
            );
            return $response->withJson(['success' => false]);
        } catch(\Exception $e){
            return $response->withJson(['success' => false]);
        }
    }

    public function getWidget($request, $response, $args){
        $widget = $request->getParam('widget');

        $html = Widget::getWidget($widget);
        if ($html){
            return $response->withJson([
                'success' => true,
                'html' => $html
            ]);
        }
    }

    public function updateCheck($request, $response, $args){
        if (!Session::isRole(['owner', 'admin'])){
            return $response->withStatus(403);
        }
        $updates = Update::checkForUpdates();
        
        $html = Admin::render('partials/update-table',[
            'updates' => $updates
        ]);

        return $response->withJson([
            'success' => true,
            'html' => $html
        ]);
    }

    public function runUpdate($request, $response, $args){
        if (!Session::isRole(['owner', 'admin'])){
            return $response->withStatus(403);
        }
        $params = $request->getParams();
        $bRet = Update::doUpdate($params);

        return $response->withJson(['success' => $bRet]);
    }
}