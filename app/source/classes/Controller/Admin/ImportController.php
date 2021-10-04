<?php
declare(strict_types=1);

namespace Leafpub\Controller\Admin;

use Leafpub\Admin;
use Leafpub\Language;
use Leafpub\Leafpub;
use Leafpub\Session;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ImportController
 * @package Leafpub\Controller\Admin
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class ImportController
{
    /**
     * Show the import page
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array               $args
     *
     **/
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        // Only the blog owner can import another blog
        if (!Session::isRole('owner')) {
            return $this->notFound($request, $response);
        }

        // Search for available dropins
        foreach (Leafpub::scanDir(Leafpub::path('source/classes/Importer/Dropins/')) as $file) {
            $installedImporter[] = Leafpub::filename($file->getFilename());
        }

        $html = Admin::render('import', [
            'title' => Language::term('import'),
            'scripts' => 'import.min.js',
            'styles' => 'import.css',
            'dropins' => $installedImporter,
        ]);

        return $response->write($html);
    }
}