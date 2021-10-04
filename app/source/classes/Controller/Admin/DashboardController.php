<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Session;
use Leafpub\Widget;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class DashboardController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class DashboardController
{
    /**
     * Redirects admin/ to admin/posts (GET)
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
        if (\Leafpub\Models\Setting::getOne('show_dashboard') === 'on') {
            $html = Admin::render('dashboard', [
                'title' => Language::term('dashboard'),
                'scripts' => 'dashboard.min.js',
                'styles' => 'dashboard.css',
                'dashboard' => Widget::renderDashboard(Session::user('slug')),
                //'widgets' => Widget::getWidgets()
            ]);

            return $response->write($html);
        }

        return $response->withRedirect(Admin::url('posts'));
    }
}