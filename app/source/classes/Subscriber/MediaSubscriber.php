<?php
declare(strict_types=1);

namespace Leafpub\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class MediaSubscriber
 * @package Leafpub\Subscriber
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class MediaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [];
    }
}