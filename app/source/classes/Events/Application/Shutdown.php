<?php
namespace Postleaf\Events\Application;

use Symfony\Component\EventDispatcher\Event;

class Shutdown extends Event {
    const NAME = 'application.shutdown';
    
    public function __construct(){
        
    }
}

?>