<?php
namespace Leafpub;

$app->add('Leafpub\Middleware:removeTrailingSlashes');
$app->add('Leafpub\Middleware:maintenance');
$app->add('Leafpub\Middleware:updateRegister');
$app->add('Leafpub\Middleware:imageMiddleware');
if (LEAFPUB_DEV){
    $app->add(new \RunTracy\Middlewares\TracyMiddleware($app));
    $app->add('Leafpub\Middleware:tracy');
}
?>