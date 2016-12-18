<?php
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */
namespace Leafpub\Listeners;

use Leafpub\Events\Application\Startup,
    Leafpub\Events\Application\Shutdown;

class Application {
    
    public function onApplicationStartup(Startup $start){
        $app = $start->getApp();
        // Now, we could load Plugins and add routes...
    }
    
    public function onApplicationShutdown(){
        
    }
}
?>