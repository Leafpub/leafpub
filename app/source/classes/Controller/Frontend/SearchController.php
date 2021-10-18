<?php
declare(strict_types=1);

namespace Leafpub\Controller\Frontend;

use Leafpub\Search;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class SearchController
 * @package Leafpub\Controller\Frontend
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class SearchController
{
    /**
     * Renders the search results
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
        $html = Search::render($args['query'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }
}