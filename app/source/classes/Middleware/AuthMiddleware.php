<?php
declare(strict_types=1);

namespace Leafpub\Middleware;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class AuthMiddleware
 * @package Leafpub\Middleware
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
final class AuthMiddleware
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface
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
                ->withRedirect(Admin::url('login?redirect=' . rawurlencode((string) $uri)));
        }

        return $next($request, $response);
    }
}