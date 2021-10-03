<?php
declare(strict_types=1);

namespace Leafpub\Middleware;

use Leafpub\Error;
use Leafpub\Language;
use Leafpub\Models\Setting;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class MaintenanceMiddleware
 * @package Leafpub\Middleware
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
final class MaintenanceMiddleware
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface
    {
        $siteInMaintenanceMode = (Setting::getOne('maintenance') === 'on');

        $allowedRoutes = ['/admin', '/admin/login', '/api/login', '/logout'];
        $tryToLogin = in_array($request->getUri()->getPath(), $allowedRoutes);

        if ($siteInMaintenanceMode && !$tryToLogin && (!Session::isAuthenticated() || (Session::isAuthenticated() && !Session::isRole(['owner', 'admin'])))) {
            $data = [
                'content' => Setting::getOne('maintenance_message'),
            ];
            $special = [
                'meta' => [
                    'title' => Language::term('maintenance'),
                    'description' => Language::term('maintenance'),
                ],
            ];
            $html = Error::render('503', $data, $special);
            return $response->withStatus(503)->write($html);
        }

        return $next($request, $response);
    }
}