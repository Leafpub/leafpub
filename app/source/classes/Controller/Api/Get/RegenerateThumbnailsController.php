<?php
declare(strict_types=1);

namespace Leafpub\Controller\Api\Get;

use Leafpub\Models\Upload;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class RegenerateThumbnailsController
 * @package Leafpub\Controller\Api\Get
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class RegenerateThumbnailsController
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        array $args
    ): ResponseInterface {
        $generatedThumbnails = Upload::regenerateThumbnails();
        // Send response
        return $response->withJson([
            'success' => true,
            'num' => $generatedThumbnails,
        ]);
    }
}