<?php
declare(strict_types=1);

namespace Leafpub\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RemoveTrailingSlashMiddleware
 * @package Leafpub\Middleware
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
final class RemoveTrailingSlashMiddleware
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        if ($path !== '/' && substr($path, -1) === '/') {
            $uri = $uri->withPath(substr($path, 0, -1));

            return $response->withRedirect((string) $uri, 301);
        }

        return $next($request, $response);
    }
}