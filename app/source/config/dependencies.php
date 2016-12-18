<?php
namespace Leafpub;

/**
* Custom handlers
**/

// Not found handler
$container['notFoundHandler'] = function($container) {
    return function($request, $response) use ($container) {
        return $response->withStatus(404)->write(Error::render());
    };
};

// Not allowed handler
$container['notAllowedHandler'] = function($container) {
    return function($request, $response, $methods) use ($container) {
        return $response->withStatus(405)->write(Error::system([
            'title' => 'Method Not Allowed',
            'message' => 'Method must be one of: ' . implode(', ', $methods)
        ]));
    };
};

// Error handlers
$container['errorHandler'] = function($container) {
    return function($request, $response, $exception) use ($container) {
        return $response->withStatus(500)->write(Error::system([
            'title' => 'Application Error',
            'message' => $exception->getMessage()
        ]));
    };
};

?>