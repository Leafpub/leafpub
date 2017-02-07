<?php
namespace Leafpub\Events;

use Symfony\Component\EventDispatcher\Event;

abstract class LeafpubEvent extends Event implements ILeafpubEvent{
    const NAME = '';
    
    protected $_data;

    // Set data to null, so an event can be just a notification without data
    public function __construct($data = null){
        $this->_data = $data;
    }

    public function getEventData(){
        return $this->_data;
    }
    
    public function setEventData($data){
        $this->_data = $data;
    }
}
?>