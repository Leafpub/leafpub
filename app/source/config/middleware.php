<?php
namespace Leafpub;

$app->add('Leafpub\Middleware:removeTrailingSlashes');
$app->add('Leafpub\Middleware::maintenance');

?>