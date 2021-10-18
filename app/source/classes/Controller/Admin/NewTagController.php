<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class NewTagController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class NewTagController
{
    /**
     * Renders the new tag view (GET)
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
        // To add tags, you must be an owner, admin, or editor
        if (!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('tags.new', [
            'title' => Language::term('new_tag'),
            'scripts' => 'tags.edit.min.js',
            'styles' => 'tags.edit.css',
            'tag' => [],
        ]);

        return $response->write($html);
    }
}