<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Models\User;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ListUsersController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class ListUsersController
{
    /**
     * Renders the user view (GET)
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
        // To view users, you must be an owner or admin
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('users', [
            'title' => Language::term('users'),
            'scripts' => 'users.min.js',
            'styles' => 'users.css',
            'users' => User::getMany([
                'items_per_page' => 50,
            ]),
        ]);

        return $response->write($html);
    }
}