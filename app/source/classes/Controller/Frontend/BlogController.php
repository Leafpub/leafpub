<?php
declare(strict_types=1);

namespace Leafpub\Controller\Frontend;

use Leafpub\Blog;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class BlogController
 * @package Leafpub\Controller\Frontend
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class BlogController
{
    /**
     * Renders the blog view
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
        $page = isset($args['page']) ? $args['page'] : false;
        $html = Blog::render($page);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }
}