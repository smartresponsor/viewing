<?php

declare(strict_types=1);

namespace App\Viewing\Subscriber\View;

use App\Viewing\ServiceInterface\View\ViewObservabilityServiceInterface;
use App\Viewing\ServiceInterface\View\ViewResponseGuardServiceInterface;
use App\Viewing\ServiceInterface\View\ViewRouteExclusionServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class ViewKernelResponseGuardSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ViewResponseGuardServiceInterface $responseGuardService,
        private ViewRouteExclusionServiceInterface $routeExclusionService,
        private ?ViewObservabilityServiceInterface $observability = null,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -64],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($this->routeExclusionService->isExcluded($request)) {
            return;
        }

        if (!$this->responseGuardService->isViolation($request, $response)) {
            return;
        }

        $routeName = $request->attributes->get('_route');
        $this->observability?->record('guard_violation', [
            'route' => \is_string($routeName) ? $routeName : null,
            'path' => $request->getPathInfo(),
            'guard_mode' => $this->responseGuardService->mode(),
            'status_code' => $response->getStatusCode(),
        ], 'warning');

        if ('observe' === $this->responseGuardService->mode()) {
            $event->setResponse($this->responseGuardService->observe($response));

            return;
        }

        $event->setResponse($this->responseGuardService->replacement($request, $response));
    }
}
