<?php
declare(strict_types=1);
/**
 * Leafpub: Simple, beautiful publishing. (https://leafpub.org)
 *
 * @link      https://github.com/Leafpub/leafpub
 * @copyright Copyright (c) 2016 Leafpub Team
 * @license   https://github.com/Leafpub/leafpub/blob/master/LICENSE.md (GPL License)
 */

namespace Leafpub\Events;

use Symfony\Component\EventDispatcher\Event;

abstract class LeafpubEvent extends Event implements ILeafpubEvent
{
    public const NAME = '';

    protected $_data;

    // Set data to null, so an event can be just a notification without data
    public function __construct($data = null)
    {
        $this->_data = $data;
    }

    public function getEventData()
    {
        return $this->_data;
    }

    public function setEventData($data)
    {
        $this->_data = $data;
    }
}
