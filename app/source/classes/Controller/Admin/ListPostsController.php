<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Models\Post;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ListPostsController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class ListPostsController
{
    /**
     * Renders the posts view view (GET)
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
        $html = Admin::render('posts', [
            'title' => Language::term('posts'),
            'scripts' => 'posts.min.js',
            'styles' => 'posts.css',
            'posts' => Post::getMany([
                // If you're not an owner, admin, or editor then you can only see your own posts
                'author' => Session::isRole(['owner', 'admin', 'editor']) ? null : Session::user('slug'),
                'status' => null,
                'ignore_featured' => false,
                'ignore_pages' => false,
                'ignore_sticky' => false,
                'items_per_page' => 50,
                'end_date' => null,
            ], $pagination),
            'pagination' => $pagination,
        ]);

        return $response->write($html);
    }
}