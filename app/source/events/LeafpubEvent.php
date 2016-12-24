<?php
namespace Leafpub\Events;

use Symfony\Component\EventDispatcher\Event;

abstract class LeafpubEvent extends Event implements ILeafpubEvent{
    const NAME = '';
    
    protected $_data;

    public function __construct($data){
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