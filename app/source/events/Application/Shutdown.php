<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
namespace Leafpub\Events\Application;

use Symfony\Component\EventDispatcher\Event;

class Shutdown extends Event {
    const NAME = 'application.shutdown';
    
    public function __construct(){
        
    }
}

?>