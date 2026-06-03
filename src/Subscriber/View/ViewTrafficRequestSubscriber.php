<?php

declare(strict_types=1);

namespace App\Viewing\Subscriber\View;

use App\Viewing\ServiceInterface\View\ViewRouteExclusionServiceInterface;
use App\Viewing\ServiceInterface\View\ViewTrafficClassifierInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ViewTrafficRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ViewTrafficClassifierInterface $trafficClassifier,
        private ViewRouteExclusionServiceInterface $routeExclusionService,
        private string $actorRequestAttribute = '_view_actor_type',
        private bool $enabled = true,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->enabled) {
            return;
        }

        $request = $event->getRequest();

        if ($this->routeExclusionService->isExcluded($request)) {
            return;
        }

        if (null !== $request->attributes->get($this->actorRequestAttribute)) {
            return;
        }

        $actorType = $this->trafficClassifier->classify($request);

        if (null === $actorType) {
            return;
        }

        $request->attributes->set($this->actorRequestAttribute, $actorType);
    }
}
