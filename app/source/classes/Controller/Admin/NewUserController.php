<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class NewUserController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class NewUserController
{
    /**
     * Renders the new user view (GET)
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     * @return ResponseInterface
     *
     **/
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // To add users, you must be an owner or admin
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('users.new', [
            'title' => Language::term('new_user'),
            'scripts' => 'users.edit.min.js',
            'styles' => 'users.edit.css',
            'user' => [],
            'redirect' => Admin::url('users'),
        ]);

        return $response->write($html);
    }
}