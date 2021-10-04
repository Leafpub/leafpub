<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Leafpub;
use Leafpub\Models\Tag;
use Leafpub\Models\User;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class NewPostController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class NewPostController
{
    /**
     * Renders the new post view (GET)
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
                'api/posts/render?new&zen=' . rawurlencode($_COOKIE['zen'] ?? '')
            ),
        ]);

        return $response->write($html);
    }
}