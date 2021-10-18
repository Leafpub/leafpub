<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Models\Plugin;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PluginController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class PluginController
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }
        $html = Admin::render('plugins', [
            'title' => Language::term('plugins'),
            'scripts' => 'plugins.min.js',
            'styles' => 'plugins.css',
            'plugins' => Plugin::getMany(),
        ]);

        return $response->write($html);
    }
}