<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Models\Tag;
use Leafpub\Models\Upload;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class MediaController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class MediaController
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $uploads = Upload::getMany([
            'items_per_page' => 20,
        ], $pagination);

        $html = Admin::render('uploads', [
            'title' => Language::term('uploads'),
            'scripts' => 'uploads.min.js',
            'styles' => 'uploads.css',
            'uploads' => $uploads,
            'all_tags' => Tag::getNames('upload'),
            'post_tags' => [],
            'can_create_tags' => Session::isRole(['owner', 'admin', 'editor']) ? 'true' : 'false',
        ]);

        return $response->write($html);
    }
}