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
 * Class EditTagController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class EditTagController
{
    /**
     * Renders the edit tag view (GET)
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
        // To edit tags, you must be an owner, admin, or editor
        if (!Session::isRole(['owner', 'admin', 'editor'])) {
            return $this->notFound($request, $response);
        }

        $tag = Tag::getOne($args['slug']);
        if (!$tag) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('tags.edit', [
            'title' => Language::term('edit_tag'),
            'scripts' => 'tags.edit.min.js',
            'styles' => 'tags.edit.css',
            'tag' => $tag,
        ]);

        return $response->write($html);
    }
}