<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub;

use Leafpub\Models\Setting;

/**
* Middleware
*
* PSR 7 compatible middleware for use with Leafpub's router
* @package Leafpub
*
**/ 
class Middleware {

    /**
    * Redirect {path}/page/0 and {path}/page/1 to {path}
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param callable $next
    * @return \Slim\Http\Response
    *
    **/
    public function adjustPageNumbers($request, $response, $next) {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $pagination = Setting::getOne('frag_page');
        $length = strlen("/$pagination/0");
        $suffix = substr($path, -$length);

        if($suffix === '/page/0' || $suffix === '/page/1') {
            $uri = $uri->withPath(substr($path, 0, strlen($path) - $length));
            return $response->withRedirect((string) $uri, 301);
        }

        return $next($request, $response);
    }

    /**
    * Redirect ?s=query to /search/query
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param callable $next
    * @return \Slim\Http\Response
    *
    **/
    public function adjustSearchQuery($request, $response, $next) {
        if(isset($request->getQueryParams()['s'])) {
            return $response->withRedirect(Search::url($request->getQueryParams()['s']));
        }
        return $next($request, $response);
    }

    /**
    * Remove trailing slashes from requests
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param callable $next
    * @return \Slim\Http\Response
    *
    **/
    public function removeTrailingSlashes($request, $response, $next) {
        $uri = $request->getUri();
        $path = $uri->getPath();
        if($path !== '/' && substr($path, -1) === '/') {
            $uri = $uri->withPath(substr($path, 0, -1));
            return $response->withRedirect((string) $uri, 301);
        }

        return $next($request, $response);
    }

    /**
    * Requires an authenticated user
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param callable $next
    * @return \Slim\Http\Response
    *
    **/
    public function requireAuth($request, $response, $next) {
        $uri = $request->getUri();

        if(!Session::isAuthenticated()) {
            // Is this an AJAX request?
            if($request->isXhr()) {
                // Send back a JSON response
                return $response->withJson([
                    'success' => false,
                    'message' => Language::term('please_login_again_to_complete_your_request'),
                    'language' => [
                        'username' => Language::term('username'),
                        'password' => Language::term('password'),
                    ]
                ], 401); // Unauthorized
            } else {
                // Redirect to the login page
                return $response
                    ->withRedirect(Admin::url('login?redirect=' . rawurlencode($uri)), 401);
            }
        }

        return $next($request, $response);
    }

    /** 
    *   Maintenance Middleware
    *
    *   Checks, if the site is in maintenance mode. 
    *   Only the owner and admins see the site after login
    *   All other see maintenance.hbs
    *
    * @param \Slim\Http\Request $request
    * @param \Slim\Http\Response $response
    * @param callable $next
    * @return \Slim\Http\Response
    *
    **/
    public static function maintenance($request, $response, $next){
        $siteInMaintenanceMode = (Setting::getOne('maintenance') == 'on');
        
        $allowedRoutes = array('/admin', '/admin/login', '/api/login', '/logout');
        $tryToLogin = in_array($request->getUri()->getPath(), $allowedRoutes);
        
        if ($siteInMaintenanceMode && !$tryToLogin ){
            if (!Session::isAuthenticated() || (Session::isAuthenticated() && !Session::isRole(array('owner', 'admin')))){
                $data = array(
                    'content' => Setting::getOne('maintenance_message')
                );

                $special = array(
                    'meta' => array(
                        'title' => Language::term('maintenance'),
                        'description' => Language::term('maintenance')
                    )
                );
                $html = Error::render('503', $data, $special);
                return $response->withStatus(503)->write($html);
            }
        }
        return $next($request, $response);
    }

    public static function checkDBScheme($request, $response, $next){
        if (version_compare(LEAFPUB_SCHEME_VERSION, (\Leafpub\Models\Setting::getOne('schemeVersion') ?: 0)) == 1){
            if (Session::isRole(['owner', 'admin'])){
                $allowedRoutes = array('/api/update', '/admin', '/admin/login', '/admin/updateLeafpub', '/logout', '/api/login');
                $tryToUpdate = in_array($request->getUri()->getPath(), $allowedRoutes);
                if (!$tryToUpdate){
                    return $response->withRedirect(Admin::url('updateLeafpub'), 401);
                }
            }
        }
        return $next($request, $response);
    }

}