<?php
namespace Leafpub;

$app->add('Leafpub\Middleware:removeTrailingSlashes');
$app->add('Leafpub\Middleware::maintenance');
$app->add(function($req, $res, $next){
    Leafpub::on(Events\Post\BeforeRender::NAME, function($event){
        $data = &$event->getEventData();
        //var_dump($data); exit;
        //$data['special_vars'] = array();
    });
    return $next($req, $res);
});
?>