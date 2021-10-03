<?php
declare(strict_types=1);

namespace Leafpub\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PostSubscriber
 * @package Leafpub\Subscriber
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class PostSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [];
    }
}