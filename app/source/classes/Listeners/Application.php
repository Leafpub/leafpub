<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Listeners;

use Leafpub\Events\Application\Shutdown;
use Leafpub\Events\Application\Startup;

class Application
{
    public function onApplicationStartup(Startup $start)
    {
        $app = $start->getEventData();
        // Now, we could load Plugins and add routes...
    }

    public function onApplicationShutdown(Shutdown $end)
    {
        //$end->getEventData();
    }
}
