<?php

declare(strict_types=1);

namespace App\Viewing\Service;

use App\Viewing\ServiceInterface\ViewResponseGuardServiceInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ViewResponseGuardService implements ViewResponseGuardServiceInterface
{
    public function __construct(
        private string $controlledRouteAttribute = '_view_controlled',
        private string $guardMode = 'enforce',
        private bool $debug = false,
    ) {
        if (!\in_array($this->guardMode, ['off', 'observe', 'enforce'], true)) {
            throw new \InvalidArgumentException('Response guard mode must be off, observe, or enforce.');
        }
    }

    public function mode(): string
    {
        return $this->guardMode;
    }

    public function isViolation(Request $request, Response $response): bool
    {
        if ('off' === $this->guardMode) {
            return false;
        }

        if (true !== $request->attributes->getBoolean($this->controlledRouteAttribute, false)) {
            return false;
        }

        if ($response instanceof JsonResponse || $response instanceof RedirectResponse || $response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        if ($response->isRedirection() || Response::HTTP_NO_CONTENT === $response->getStatusCode()) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        $content = (string) $response->getContent();
        $looksLikeHtml = str_contains($contentType, 'text/html') || str_contains(ltrim($content), '<!DOCTYPE html') || str_contains(ltrim($content), '<html');

        if (!$looksLikeHtml) {
            return false;
        }

        if ($response->headers->has('X-Viewing-Rendered')) {
            return false;
        }

        return true;
    }

    public function observe(Response $response): Response
    {
        $response->headers->set('X-Viewing-Guard', 'observed-illegal-controller-render');

        return $response;
    }

    public function replacement(Request $request, Response $response): Response
    {
        $payload = [
            'ok' => false,
            'type' => 'viewing_illegal_controller_render',
            'message' => 'A controlled producer route returned pre-rendered HTML. Viewing replaced the response to preserve the central view boundary.',
            'route' => $request->attributes->get('_route'),
            'path' => $request->getPathInfo(),
        ];

        if ($this->debug) {
            $payload['debug'] = [
                'status_code' => $response->getStatusCode(),
                'content_type' => $response->headers->get('Content-Type'),
                'controller' => $request->attributes->get('_controller'),
                'content_length' => \strlen((string) $response->getContent()),
            ];
        }

        $replacement = new JsonResponse($payload, Response::HTTP_INTERNAL_SERVER_ERROR);
        $replacement->headers->set('X-Viewing-Guard', 'illegal-controller-render');

        return $replacement;
    }
}
