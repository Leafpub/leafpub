<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RecoverController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class RecoverController
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // Log out the current user if they requested this page
        Session::logout();

        $html = Admin::render('login.recover', [
            'title' => Language::term('lost_your_password'),
            'scripts' => ['login.min.js'],
            'styles' => 'login.css',
            'body_class' => 'no-menu',
        ]);

        return $response->write($html);
    }
}