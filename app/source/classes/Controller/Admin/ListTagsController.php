<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Models\Tag;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ListTagsController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class ListTagsController
{
    /**
     * Renders the tags view (GET)
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
        // To view tags, you must be an owner, admin, or editor
        if (!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('tags', [
            'title' => Language::term('tags'),
            'scripts' => 'tags.min.js',
            'styles' => 'tags.css',
            'tags' => Tag::getMany([
                'items_per_page' => 50,
            ]),
        ]);

        return $response->write($html);
    }
}