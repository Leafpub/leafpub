<?php
declare(strict_types=1);

namespace Leafpub\Middleware;

use Leafpub\Leafpub;
use Leafpub\Models\Upload;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ImageMiddleware
 * @package Leafpub\Middleware
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
final class ImageMiddleware
{
    public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface
    {
        $quality = 50;
        if (stripos($request->getUri()->getPath(), 'img/') !== false) {
            $pic = Leafpub::fileName($request->getUri()->getPath()); // pic == /img/filename.jpg
            $picData = Upload::getOne($pic);
            if (!$picData) {
                return $response->withStatus(403);
            }
            $params = $request->getParams();
            if ($params) {
                if (!isset($params['sign']) || $params['sign'] !== $picData['sign']) {
                    return $response->withStatus(403);
                }
                $dir = Leafpub::path('var/cache/' . $pic);
                if (!mkdir($dir) && !is_dir($dir)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
                }
                foreach (array_keys($params) as $key) {
                    $pic .= '-' . $key . $params[$key];
                }
                $pic .= '.' . $picData['extension'];
                $mime = '';
                if ($picData['extension'] === 'gif') {
                    return $response->withHeader('Content-type', mime_content_type($picData['path']))->write(file_get_contents($picData['path']));
                }
                if (is_file($dir . '/' . $pic)) {
                    // We have a cached image, so deliver it.
                    return $response->withHeader('Content-type', mime_content_type($dir . '/' . $pic))->write(file_get_contents($dir . '/' . $pic));
                }
                $simpleImage = new \claviska\SimpleImage();
                $simpleImage->fromFile(Leafpub::path($picData['path']));
                if (isset($params['width'])) {
                    $quality = $params['width'] < 1000 ? 100 : 50;
                    $simpleImage->fitToWidth($params['width']);
                }
                if (isset($params['blur'])) {
                    $simpleImage->blur('gaussian', $params['blur']);
                }
                if (isset($params['sepia'])) {
                    $simpleImage->sepia();
                }
                if (isset($params['emboss'])) {
                    $simpleImage->emboss();
                }
                if (isset($params['grayscale'])) {
                    $simpleImage->desaturate();
                }
                if (isset($params['brighten'])) {
                    $simpleImage->brighten($params['brighten'] ?: 0);
                }
                $simpleImage->toFile($dir . '/' . $pic, null, $quality);
                $stream = new \Slim\Http\Stream(fopen($dir . '/' . $pic, 'rb'));

                return $response
                    ->withHeader('Content-type', mime_content_type($dir . '/' . $pic))
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $pic . '"')
                    ->withBody($stream);
            }
            $file = Leafpub::path($picData['path']);
            $stream = new \Slim\Http\Stream(fopen($file, 'rb'));

            return $response
                ->withHeader('Content-type', mime_content_type($file))
                ->withHeader('Content-Disposition', 'attachment; filename="' . $picData['filename'] . '"')
                ->withBody($stream);
            //return $response->withHeader('Content-type', 'image')->write(file_get_contents(\Leafpub\Leafpub::path($picData['path'])));
        }

        return $next($request, $response);
    }
}