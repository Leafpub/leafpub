<?php
declare(strict_types=1);

namespace Leafpub\Controller\Frontend;

use Leafpub\Events\Post\PostViewed;
use Leafpub\Models\Post;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PostController
 * @package Leafpub\Controller\Frontend
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class PostController
{
    /**
     * Renders a specific post
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     * @throws \Exception
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $preview = Session::isAuthenticated() && isset($request->getParams()['preview']);
        $html = Post::render($args['post'], [
            // Render this post as a preview if the user is logged in and ?preview is in the URL
            'preview' => $preview,
        ]);

        if ($html === false) {
            return $this->notFound($request, $response);
        }
        if (!$preview) {
            $ev = new PostViewed(['post' => $args['post'], 'request' => $request]);
            \Leafpub\Leafpub::dispatchEvent(PostViewed::NAME, $ev);
        }

        return $response->write($html);
    }
}