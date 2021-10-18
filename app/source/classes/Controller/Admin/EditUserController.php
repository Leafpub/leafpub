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
 * Class EditUserController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class EditUserController
{
    /**
     * Renders the edit user view (GET)
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
        // To edit a user, you must be an owner, admin or the user
        if (!Session::isRole(['owner', 'admin']) && $args['slug'] !== Session::user('slug')) {
            return $this->notFound($request, $response);
        }

        // Get the user
        $user = User::getOne($args['slug']);
        if (!$user) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('users.edit', [
            'title' => Language::term('edit_user'),
            'scripts' => 'users.edit.min.js',
            'styles' => 'users.edit.css',
            'user' => $user,
            'redirect' => Session::isRole(['owner', 'admin']) ?
                Admin::url('users') : Admin::url('posts'),
        ]);

        return $response->write($html);
    }
}