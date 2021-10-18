<?php
declare(strict_types=1);

namespace Leafpub\Controller\Frontend;

use Leafpub\Models\Tag;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class TagController
 * @package Leafpub\Controller\Frontend
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class TagController
{
    /**
     * Renders the tag view
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     *
     * @return ResponseInterface
     *
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $html = Tag::render($args['tag'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }
}