<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class UpdateController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class UpdateController
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        if (!Session::isRole(['owner', 'admin'])) {
            return $response->withRedirect(Admin::url());
        }

        if (version_compare(LEAFPUB_SCHEME_VERSION, (\Leafpub\Models\Setting::getOne('schemeVersion') ?: 0)) < 1) {
            return $response->withRedirect(Admin::url());
        }

        $html = Admin::render('update', [
            'title' => Language::term('update'),
            'scripts' => 'update.min.js',
            //'styles' => 'update.css',
            'dbScheme' => \Leafpub\Models\Setting::getOne('schemeVersion') ?: 0,
            'schemeVersion' => LEAFPUB_SCHEME_VERSION,
        ]);

        return $response->write($html);
    }
}