<?php
namespace Postleaf\Controller;

use Interop\Container\ContainerInterface;

class Controller {

    protected $ci;

    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;
    }

    protected function notFound($request, $response) {
        $this->ci['notFoundHandler']($request, $response);
        return $response->withStatus(404);
    }

}