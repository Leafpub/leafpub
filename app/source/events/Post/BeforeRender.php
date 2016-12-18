<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
 namespace Leafpub\Events\Post;

use Symfony\Component\EventDispatcher\Event;

class BeforeRender extends Event {
    const NAME = 'post.beforeRender';
    
    protected $_eventData;
    
    public function __construct(Array $eventData){
        $this->_eventData = $eventData;
    }
    
    public function &getEventData(){
        return $this->_eventData;
    }
}

?>