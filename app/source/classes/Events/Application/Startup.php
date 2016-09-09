<?php
namespace Postleaf\Events\Application;

use Symfony\Component\EventDispatcher\Event;

class Startup extends Event {
    const NAME = 'application.startup';
    
    protected $app;
    
    public function __construct(\Slim\App $app){
        $this->app = $app;
    }
    
    public function getApp(){
        return $this->app;
    }
}

?>