<?php
declare(strict_types=1);

namespace Leafpub\Controller\Frontend;

use Leafpub\Error;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ErrorController
 * @package Leafpub\Controller\Frontend
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class ErrorController
{
    /**
     * Renders the error view
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
        $html = Error::render();

        return $response->write($html);
    }
}