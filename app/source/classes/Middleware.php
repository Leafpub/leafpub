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
    public function adjustPageNumbers($request, $response, $next)
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
    public function adjustSearchQuery($request, $response, $next)
    {
        if (isset($request->getQueryParams()['s'])) {
            return $response->withRedirect(Search::url($request->getQueryParams()['s']));
        }

        return $next($request, $response);
    }

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
    public function removeTrailingSlashes($request, $response, $next)
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        if ($path !== '/' && substr($path, -1) === '/') {
            $uri = $uri->withPath(substr($path, 0, -1));

            return $response->withRedirect((string) $uri, 301);
        }

        return $next($request, $response);
    }

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
    public function requireAuth($request, $response, $next)
    {
        $uri = $request->getUri();

        if (!Session::isAuthenticated()) {
            // Is this an AJAX request?
            if ($request->isXhr()) {
                // Send back a JSON response
                return $response->withJson([
                    'success' => false,
                    'message' => Language::term('please_login_again_to_complete_your_request'),
                    'language' => [
                        'username' => Language::term('username'),
                        'password' => Language::term('password'),
                    ],
                ], 401); // Unauthorized
            }
            // Redirect to the login page
            return $response
                    ->withRedirect(Admin::url('login?redirect=' . rawurlencode((string) $uri)), 401);
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
     * @param \Slim\Http\Request  $request
     * @param \Slim\Http\Response $response
     * @param callable            $next
     *
     * @return \Slim\Http\Response
     *
     **/
    public function maintenance($request, $response, $next)
    {
        $siteInMaintenanceMode = (Setting::getOne('maintenance') == 'on');

        $allowedRoutes = ['/admin', '/admin/login', '/api/login', '/logout'];
        $tryToLogin = in_array($request->getUri()->getPath(), $allowedRoutes);

        if ($siteInMaintenanceMode && !$tryToLogin) {
            if (!Session::isAuthenticated() || (Session::isAuthenticated() && !Session::isRole(['owner', 'admin']))) {
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
        }

        return $next($request, $response);
    }

    public function checkDBScheme($request, $response, $next)
    {
        if (version_compare(LEAFPUB_SCHEME_VERSION, (\Leafpub\Models\Setting::getOne('schemeVersion') ?: 0)) === 1) {
            if (Session::isRole(['owner', 'admin'])) {
                $allowedRoutes = ['/api/update', '/admin', '/admin/login', '/admin/updateLeafpub', '/logout', '/api/login'];
                $tryToUpdate = in_array($request->getUri()->getPath(), $allowedRoutes, true);
                if (!$tryToUpdate) {
                    return $response->withRedirect(Admin::url('updateLeafpub'), 401);
                }
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

    public function imageMiddleware($request, $response, $next)
    {
        $quality = 50;
        if (stripos($request->getUri()->getPath(), 'img/') !== false) {
            $pic = Leafpub::fileName($request->getUri()->getPath(), 5); // pic == /img/filename.jpg
            $picData = Upload::getOne($pic);
            if (!$picData) {
                return $response->withStatus(403);
            }
            $params = $request->getParams();
            if ($params) {
                if (!isset($params['sign']) || $params['sign'] !== $picData['sign']) {
                    return $response->withStatus(403);
                }
                $dir = Leafpub::path('content/cache/' . $pic);
                if (!mkdir($dir) && !is_dir($dir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
                }
                foreach (array_keys($params) as $key) {
                    $pic .= '-' . $key . $params[$key];
                }
                $pic .= '.' . $picData['extension'];
                $mime = '';
                if ($picData['extension'] === 'gif') {
                    return $response->withHeader('Content-type', mime_content_type($picData['path']))->write(file_get_contents($picData['path']));
                }
                if (is_file($dir . '/' . $pic)) {
                    // We have a cached image, so deliver it.
                    return $response->withHeader('Content-type', mime_content_type($dir . '/' . $pic))->write(file_get_contents($dir . '/' . $pic));
                }
                $simpleImage = new \claviska\SimpleImage();
                $simpleImage->fromFile(Leafpub::path($picData['path']));
                if (isset($params['width'])) {
                    $quality = $params['width'] < 1000 ? 100 : 50;
                    $simpleImage->fitToWidth($params['width']);
                }
                if (isset($params['blur'])) {
                    $simpleImage->blur('gaussian', $params['blur']);
                }
                if (isset($params['sepia'])) {
                    $simpleImage->sepia();
                }
                if (isset($params['emboss'])) {
                    $simpleImage->emboss();
                }
                if (isset($params['grayscale'])) {
                    $simpleImage->desaturate();
                }
                if (isset($params['brighten'])) {
                    $simpleImage->brighten($params['brighten'] ?: 0);
                }
                $simpleImage->toFile($dir . '/' . $pic, null, $quality);
                $stream = new \Slim\Http\Stream(fopen($dir . '/' . $pic, 'rb'));

                return $response
                        ->withHeader('Content-type', mime_content_type($dir . '/' . $pic))
                        ->withHeader('Content-Disposition', 'attachment; filename="' . $pic . '"')
                        ->withBody($stream);
            }
            $file = Leafpub::path($picData['path']);
            $stream = new \Slim\Http\Stream(fopen($file, 'rb'));

            return $response
                        ->withHeader('Content-type', mime_content_type($file))
                        ->withHeader('Content-Disposition', 'attachment; filename="' . $picData['filename'] . '"')
                        ->withBody($stream);
            //return $response->withHeader('Content-type', 'image')->write(file_get_contents(\Leafpub\Leafpub::path($picData['path'])));
        }

        return $next($request, $response);
    }

    public function tracy($request, $response, $next)
    {
        if (LEAFPUB_DEV) {
            Debugger::enable(Debugger::DEVELOPMENT, Leafpub::path('log/'));
            Debugger::getBar()->addPanel(new Panel(), "PerformancePanel");
            if ($request->getParsedBody()) {
                Debugger::barDump($request->getParsedBody(), 'ParsedBody');
            }

            return $next($request, $response);
        }
    }
}
