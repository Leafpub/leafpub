<?php
declare(strict_types=1);

namespace Leafpub\Controller\Frontend;

use Leafpub\Models\Post;
use Leafpub\Models\Setting;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class CustomHomepageController
 * @package Leafpub\Controller\Frontend
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class CustomHomepageController
{
    /**
     * Renders the custom homepage
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
        $html = Post::render(Setting::getOne('homepage'));

        return $html === false ?
            $this->notFound($request, $response) :
            $response->write($html);
    }
}