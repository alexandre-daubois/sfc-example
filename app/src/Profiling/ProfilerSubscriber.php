<?php

namespace App\Profiling;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ProfilerSubscriber
{
    private bool $profiling = false;

    #[AsEventListener(KernelEvents::REQUEST, priority: 4096)]
    public function start(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getRequest()->headers->has('X-Spx-Profile')) {
            return;
        }

        spx_profiler_start();
        $this->profiling = true;
    }

    #[AsEventListener(KernelEvents::RESPONSE, priority: -4096)]
    public function stop(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->profiling) {
            return;
        }

        $this->profiling = false;
        $event->getResponse()->headers->set('X-Spx-Report', (string) spx_profiler_stop());
    }
}
