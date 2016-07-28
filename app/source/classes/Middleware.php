<?php
//
// Postleaf\Middleware: PSR 7 compatible middleware for use with Postleaf's router
//
namespace Postleaf;

class Middleware {

    // Redirect {path}/page/0 and {path}/page/1 to {path}
    public function adjustPageNumbers($request, $response, $next) {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $pagination = Setting::get('frag_page');
        $length = strlen("/$pagination/0");
        $suffix = substr($path, -$length);

        if($suffix === '/page/0' || $suffix === '/page/1') {
            $uri = $uri->withPath(substr($path, 0, strlen($path) - $length));
            return $response->withRedirect((string) $uri, 301);
        }

        return $next($request, $response);
    }

    // Redirect ?s=query to /search/query
    public function adjustSearchQuery($request, $response, $next) {
        if(isset($request->getQueryParams()['s'])) {
            return $response->withRedirect(Search::url($request->getQueryParams()['s']));
        }
        return $next($request, $response);
    }

    // Remove trailing slashes from requests
    public function removeTrailingSlashes($request, $response, $next) {
        $uri = $request->getUri();
        $path = $uri->getPath();
        if($path !== '/' && substr($path, -1) === '/') {
            $uri = $uri->withPath(substr($path, 0, -1));
            return $response->withRedirect((string) $uri, 301);
        }

        return $next($request, $response);
    }

    // Requires an authenticated user
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

}