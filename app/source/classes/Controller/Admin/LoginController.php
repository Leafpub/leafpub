<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class LoginController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class LoginController
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface
    {
        $params = $request->getParams();

        // Redirect to the admin page if they're already logged in
        if (Session::isAuthenticated()) {
            return $response->withRedirect(Admin::url());
        }

        $html = Admin::render('login', [
            'title' => Language::term('login'),
            'scripts' => ['login.min.js'],
            'styles' => 'login.css',
            'body_class' => 'no-menu',
            'redirect' => $params['redirect'],
        ]);

        return $response->write($html);
    }
}