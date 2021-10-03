<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Models\Setting;
use Leafpub\Models\Upload;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tracy\Debugger;
use Zarganwar\PerformancePanel\Panel;

/**
 * Middleware
 *
 * PSR 7 compatible middleware for use with Leafpub's router
 *
 **/
class Middleware
{
    /**
     * Redirect {path}/page/0 and {path}/page/1 to {path}
     *
     * @param \Slim\Http\Request  $request
     * @param \Slim\Http\Response $response
     * @param callable            $next
     *
     * @return \Slim\Http\Response
     *
     **/


    /**
     * Redirect ?s=query to /search/query
     *
     * @param \Slim\Http\Request  $request
     * @param \Slim\Http\Response $response
     * @param callable            $next
     *
     * @return \Slim\Http\Response
     *
     **/


    /**
     * Remove trailing slashes from requests
     *
     * @param \Slim\Http\Request  $request
     * @param \Slim\Http\Response $response
     * @param callable            $next
     *
     * @return \Slim\Http\Response
     *
     **/


    /**
     * Requires an authenticated user
     *
     * @param \Slim\Http\Request  $request
     * @param \Slim\Http\Response $response
     * @param callable            $next
     *
     * @return \Slim\Http\Response
     *
     **/


    /**
     *   Maintenance Middleware
     *
     *   Checks, if the site is in maintenance mode.
     *   Only the owner and admins see the site after login
     *   All other see maintenance.hbs
     *
     * @param \Slim\Http\Request  $request
     * @param \Slim\Http\Response $response
     * @param callable            $next
     *
     * @return \Slim\Http\Response
     *
     **/


    public function checkDBScheme($request, $response, $next)
    {
        if (version_compare(LEAFPUB_SCHEME_VERSION, (\Leafpub\Models\Setting::getOne('schemeVersion') ?: 0)) === 1 && Session::isRole(['owner', 'admin'])) {
            $allowedRoutes = ['/api/update', '/admin', '/admin/login', '/admin/updateLeafpub', '/logout', '/api/login'];
            $tryToUpdate = in_array($request->getUri()->getPath(), $allowedRoutes, true);
            if (!$tryToUpdate) {
                return $response->withRedirect(Admin::url('updateLeafpub'), 401);
            }
        }

        return $next($request, $response);
    }

    public function updateRegister($request, $response, $next)
    {
        $time = Setting::getOne('updateTime');
        if ($time === date('H:i:s')) {
            \Leafpub\Update::updateRegisterFiles();
        }

        return $next($request, $response);
    }


}
