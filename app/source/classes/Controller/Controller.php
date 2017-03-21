<?php
/**
 * Leafpub (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
namespace Leafpub\Controller;

use Interop\Container\ContainerInterface;

/**
* Controller
*
* @package Leafpub\Controller
* @link 
**/
class Controller {

    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    protected function notFound($request, $response) {
        $this->ci['notFoundHandler']($request, $response);
        return $response->withStatus(404);
    }

    protected function returnJson($request){
        return (bool) ($request->getParam('returnJson') != null);
    }

    public function __get($name){
        return $this->ci->get($name);
    }
}