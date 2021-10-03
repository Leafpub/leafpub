<?php
declare(strict_types=1);

namespace Leafpub\Middleware;

use Leafpub\Leafpub;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tracy\Debugger;
use Zarganwar\PerformancePanel\Panel;

/**
 * Class TracyMiddleware
 * @package Leafpub\Middleware
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
final class TracyMiddleware
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface
    {
        if (LEAFPUB_DEV) {
            Debugger::enable(Debugger::DEVELOPMENT, Leafpub::path('var/log/'));
            Debugger::getBar()->addPanel(new Panel(), "PerformancePanel");
            if ($request->getParsedBody()) {
                Debugger::barDump($request->getParsedBody(), 'ParsedBody');
            }

            return $next($request, $response);
        }
    }
}