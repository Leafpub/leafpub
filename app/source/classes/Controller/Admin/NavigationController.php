<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Models\Setting;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class NavigationController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class NavigationController
{
    /**
     * Renders the navigation view (GET)
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
        $params = $request->getParams();

        // To view navigation, you must be an owner or admin
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        $html = Admin::render('navigation', [
            'title' => Language::term('navigation'),
            'scripts' => 'navigation.min.js',
            'styles' => 'navigation.css',
            'navigation' => json_decode(Setting::getOne('navigation'), true),
        ]);

        return $response->write($html);
    }
}