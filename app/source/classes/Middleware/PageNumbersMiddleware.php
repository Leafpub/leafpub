<?php
declare(strict_types=1);

namespace Leafpub\Middleware;

use Leafpub\Models\Setting;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PageNumbersMiddleware
 * @package Leafpub\Middleware
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
final class PageNumbersMiddleware
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $pagination = Setting::getOne('frag_page');
        $length = strlen("/$pagination/0");
        $suffix = substr($path, -$length);

        if ($suffix === '/page/0' || $suffix === '/page/1') {
            $uri = $uri->withPath(substr($path, 0, strlen($path) - $length));

            return $response->withRedirect((string) $uri, 301);
        }

        return $next($request, $response);
    }
}