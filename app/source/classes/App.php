<?php
declare(strict_types=1);

namespace Leafpub;

use Leafpub\Events\Application\Startup;
use Leafpub\Subscriber\ApplicationSubscriber;
use Leafpub\Subscriber\MediaSubscriber;
use Leafpub\Subscriber\PostSubscriber;
use Slim\App as Slim;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class App
 * @package Leafpub
 * @author Marc Apfelbaum <karsasmus82@gmail.com>
 */
class App extends Slim
{
    public function __construct($container = [])
    {
        parent::__construct($container);
        $this->registerSubscriber();
        $this->getContainer()->get('dispatcher')->dispatch(Startup::class, new Startup($this));
    }

    protected function registerSubscriber(): void
    {
        $container = $this->getContainer();
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $container->get('dispatcher');
        $dispatcher->addSubscriber($container->get(ApplicationSubscriber::class));
        $dispatcher->addSubscriber($container->get(PostSubscriber::class));
        $dispatcher->addSubscriber($container->get(MediaSubscriber::class));
    }
}