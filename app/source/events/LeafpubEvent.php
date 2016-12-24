<?php
namespace Leafpub\Events;

use Symfony\Component\EventDispatcher\Event;

abstract class LeafpubEvent extends Event {
    const NAME = '';
    
    public function __construct(){
        if (self::NAME == ''){
            throw new \Exception("NAME isn't set. Event needs a name!");
        }
    }
    abstract function getEventData();
    abstract function setEventData();
}
?>