<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Leafpub;
use Leafpub\Models\History;
use Leafpub\Models\Post;
use Leafpub\Models\Tag;
use Leafpub\Models\User;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class EditPostController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class EditPostController
{
    /**
     * Renders the edit post view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $post = Post::getOne($args['slug']);
        if (!$post) {
            return $this->notFound($request, $response);
        }

        // To edit a post, you must be an owner, admin, editor OR the owner of the post
        if (
            !Session::isRole(['owner', 'admin', 'editor']) &&
            $post['author'] !== Session::user('slug')
        ) {
            return $this->notFound($request, $response);
        }

        // Post is locked by another user
        if (isset($post['meta']['lock']) && $post['meta']['lock'][0] !== Session::user('slug')) {
            return $this->notFound($request, $response);
        } elseif (!isset($post['meta']['lock'])) {
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
                '&zen=' . rawurlencode($_COOKIE['zen'] ?? '')
            ),
        ]);

        return $response->write($html);
    }
}