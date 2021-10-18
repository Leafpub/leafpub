<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Backup;
use Leafpub\Language;
use Leafpub\Mailer;
use Leafpub\Models\Post;
use Leafpub\Session;
use Leafpub\Theme;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class SettingsController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class SettingsController
{
    /**
     * Renders the settings view (GET)
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
        // To edit settings, you must be an owner or admin
        if (!Session::isRole(['owner', 'admin'])) {
            return $this->notFound($request, $response);
        }

        // Get list for custom pages field and sort by title
        $pages = Post::getMany([
            'ignore_posts' => true,
            'ignore_pages' => false,
            'items_per_page' => 100,
        ]);
        usort($pages, function ($a, $b) {
            return $a['title'] > $b['title'];
        });

        // Get timezones
        $timezones = [];
        foreach (\DateTimeZone::listIdentifiers() as $tz) {
            $timezones[] = [
                'code' => $tz,
                'name' => str_replace(['_', '/'], [' ', ' / '], $tz),
            ];
        }

        // Get mailers
        $mailers = [];
        foreach (Mailer::getMailers() as $mailerName => $mailer) {
            $mailers[] = [
                'tag' => $mailerName,
                'name' => $mailer['name'],
            ];
        }

        $html = Admin::render('settings', [
            'title' => Language::term('settings'),
            'scripts' => 'settings.min.js',
            'styles' => 'settings.css',
            'pages' => $pages,
            'mailers' => $mailers,
            'backups' => Backup::getAll(),
            'languages' => Language::getAll(),
            'timezones' => $timezones,
            'themes' => Theme::getAll(),
        ]);

        return $response->write($html);
    }
}