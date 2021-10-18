<?php
declare(strict_types=1);

namespace Leafpub\Controller\Frontend;

use Leafpub\Models\User;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AuthorController
 * @package Leafpub\Controller\Frontend
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class AuthorController
{
    /**
     * Renders the author page
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array $args
     * @return ResponseInterface
     */
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $html = User::render($args['author'], $args['page']);

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }
}