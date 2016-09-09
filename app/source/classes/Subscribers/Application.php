<?php
namespace Postleaf\Subscribers;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Postleaf\Events\Application\Startup;
use Postleaf\Events\Application\Shutdown;

class Application implements EventSubscriberInterface {
    
    public static function getSubscribedEvents(){
        return array(
            Startup::NAME => 'onApplicationStartup',
            Shutdown::NAME => 'onApplicationShutdown'
        );
    }
    
    public function onApplicationStartup(Startup $start){
        $app = $start->getApp();
        // Now, we could load Plugins and add routes...
    }
    
    public function onApplicationShutdown(){
        
    }
}
?>