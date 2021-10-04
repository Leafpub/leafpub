<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Models\History;
use Leafpub\Models\Post;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PostHistoryController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class PostHistoryController
{
    /**
     * Renders the history of a post (GET)
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
        $history = History::getOne($args['id']);
        // Is there a history object and does the post's slug match the slug argument?
        if (!$history || $history['post_data']['slug'] !== $args['slug']) {
            return $this->notFound($request, $response);
        }

        // Render the revision
        $html = Post::render($history['post_data'], [
            'preview' => true,
        ]);

        return $response->write($html);
    }
}