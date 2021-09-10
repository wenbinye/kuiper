<?php

declare(strict_types=1);

namespace kuiper\swoole\listener;

use kuiper\event\EventListenerInterface;
use kuiper\swoole\event\WorkerExitEvent;
use Swoole\Timer;

class WorkerExitEventListener implements EventListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($event): void
    {
        Timer::clearAll();
    }

    public function getSubscribedEvent(): string
    {
        return WorkerExitEvent::class;
    }
}
