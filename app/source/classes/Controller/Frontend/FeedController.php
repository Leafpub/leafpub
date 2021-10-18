<?php
declare(strict_types=1);

namespace Leafpub\Controller\Frontend;

use Leafpub\Feed;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class FeedController
 * @package Leafpub\Controller\Frontend
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class FeedController
{
    /**
     * Generates the RSS Feed
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
        $html = Feed::render([
            'author' => $request->getParams()['author'],
            'tag' => $request->getParams()['tag'],
        ]);

        return $html !== '' ?
            $response
                ->withHeader('Content-type', 'application/xml')
                ->write($html) :
            $this->notFound($request, $response);
    }
}