<?php
declare(strict_types=1);

namespace Leafpub\Controller\Frontend;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class SitemapController
 * @package Leafpub\Controller\Frontend
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class SitemapController
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $xml = \Leafpub\Leafpub::generateSitemap();

        return $xml === false ?
            $this->notFound($request, $response) :
            $response->withHeader('Content-type', 'application/xml')->write($xml);
    }
}