<?php
declare(strict_types=1);

namespace Leafpub\Middleware;

use Leafpub\Search;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AdjustSearchQueryMiddleware
 * @package Leafpub\Middleware
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
final class AdjustSearchQueryMiddleware
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface
    {
        if (isset($request->getQueryParams()['s'])) {
            return $response->withRedirect(Search::url($request->getQueryParams()['s']));
        }

        return $next($request, $response);
    }
}