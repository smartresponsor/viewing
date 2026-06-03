<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewRequestContextFactoryInterface;
use App\Viewing\Value\View\ViewRequestContext;
use Symfony\Component\HttpFoundation\Request;

final readonly class ViewRequestContextFactory implements ViewRequestContextFactoryInterface
{
    public function __construct(
        private string $actorRequestAttribute = '_view_actor_type',
    ) {
    }

    public function create(Request $request): ViewRequestContext
    {
        $acceptable = $request->getAcceptableContentTypes();
        $firstAccept = $acceptable[0] ?? '';
        $actorType = $request->attributes->get($this->actorRequestAttribute);
        $routeName = $request->attributes->get('_route');

        return new ViewRequestContext(
            path: $request->getPathInfo(),
            method: $request->getMethod(),
            routeName: \is_string($routeName) ? $routeName : null,
            requestFormat: $request->getRequestFormat('html'),
            actorType: \is_string($actorType) && '' !== trim($actorType) ? trim($actorType) : null,
            prefersHtml: str_contains($firstAccept, 'text/html') || [] === $acceptable,
            prefersJson: str_contains($firstAccept, 'application/json') || str_contains($firstAccept, 'application/problem+json'),
            xmlHttpRequest: $request->isXmlHttpRequest(),
            routeAttributes: $request->attributes->all(),
        );
    }
}
