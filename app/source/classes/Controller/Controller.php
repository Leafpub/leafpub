<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Controller;

use Slim\Container;

/**
 * Controller
 *
 * @see
 **/
class Controller
{
    protected \Slim\Container $ci;

    public function __construct(Container $ci)
    {
        $this->ci = $ci;
    }

    public function __get($name)
    {
        return $this->ci->get($name);
    }

    protected function notFound($request, $response)
    {
        $this->ci['notFoundHandler']($request, $response);

        return $response->withStatus(404);
    }

    protected function returnJson($request)
    {
        return (bool) ($request->getParam('returnJson') != null);
    }
}
