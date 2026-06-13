<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\Exception\ViewPayloadException;
use App\Viewing\ServiceInterface\View\ViewPayloadNormalizerInterface;
use App\Viewing\Value\View\ViewPayload;

final class ViewPayloadNormalizer implements ViewPayloadNormalizerInterface
{
    public function supports(mixed $controllerResult): bool
    {
        if ($controllerResult instanceof ViewPayload) {
            return true;
        }

        if ($this->isSurfaceRenderableObject($controllerResult)) {
            return true;
        }

        if (!\is_array($controllerResult)) {
            return false;
        }

        return isset($controllerResult['_view']) || isset($controllerResult['_surface']);
    }

    public function normalize(mixed $controllerResult): ViewPayload
    {
        if ($controllerResult instanceof ViewPayload) {
            return $controllerResult;
        }

        if ($this->isSurfaceRenderableObject($controllerResult)) {
            return $this->normalizeSurfaceRenderableObject($controllerResult);
        }

        if (!\is_array($controllerResult)) {
            throw new ViewPayloadException('Unsupported controller result for View payload normalization.');
        }

        $view = \is_array($controllerResult['_view'] ?? null) ? $controllerResult['_view'] : [];

        $surface = $view['surface'] ?? $controllerResult['_surface'] ?? null;
        $operation = $view['operation'] ?? $controllerResult['_operation'] ?? null;

        if (!\is_string($surface) || '' === trim($surface)) {
            throw ViewPayloadException::missingRequiredField('_view.surface');
        }

        if (!\is_string($operation) || '' === trim($operation)) {
            throw ViewPayloadException::missingRequiredField('_view.operation');
        }

        $format = $view['format'] ?? $controllerResult['_format'] ?? 'auto';
        $intent = $view['intent'] ?? $controllerResult['_intent'] ?? null;
        $component = $view['component'] ?? $controllerResult['_component'] ?? null;

        return new ViewPayload(
            surface: trim($surface),
            operation: trim($operation),
            format: \is_string($format) && '' !== trim($format) ? trim($format) : 'auto',
            intent: \is_string($intent) && '' !== trim($intent) ? trim($intent) : null,
            component: \is_string($component) && '' !== trim($component) ? trim($component) : null,
            locations: $this->interfaceLocationsFromControllerResult($controllerResult),
            data: \is_array($controllerResult['data'] ?? null) ? $controllerResult['data'] : [],
            meta: \is_array($controllerResult['meta'] ?? null) ? $controllerResult['meta'] : [],
            debug: \is_array($controllerResult['debug'] ?? null) ? $controllerResult['debug'] : [],
        );
    }

    /**
     * @param array<string, mixed> $controllerResult
     *
     * @return array<string, mixed>
     */
    private function interfaceLocationsFromControllerResult(array $controllerResult): array
    {
        if (\is_array($controllerResult['interface'] ?? null) && \is_array($controllerResult['interface']['locations'] ?? null)) {
            return $controllerResult['interface']['locations'];
        }

        return \is_array($controllerResult['locations'] ?? null) ? $controllerResult['locations'] : [];
    }

    /**
     * Supports existing producer surface contracts without coupling Viewing to
     * Interfacing. The structural contract is intentionally method-based:
     * producer components can return their current surface object, and Viewing
     * becomes the only runtime render boundary.
     */
    private function isSurfaceRenderableObject(mixed $value): bool
    {
        return \is_object($value)
            && method_exists($value, 'toTemplateContext')
            && method_exists($value, 'toFallbackData');
    }

    private function normalizeSurfaceRenderableObject(object $surface): ViewPayload
    {
        /** @var array<string, mixed> $templateContext */
        $templateContext = $surface->toTemplateContext();

        /** @var array<string, mixed> $fallbackData */
        $fallbackData = $surface->toFallbackData();

        $routeContext = $this->routeContextFrom($templateContext, $fallbackData);
        $word = $this->stringFrom($templateContext['word'] ?? $fallbackData['word'] ?? null, $this->surfaceFromClass($surface::class));
        $view = $this->stringFrom($templateContext['view'] ?? $fallbackData['view'] ?? null, 'index');
        $format = $this->stringFrom($templateContext['format'] ?? $fallbackData['format'] ?? null, 'auto');
        $locations = $this->locationsFrom($templateContext, $fallbackData);
        $meta = \is_array($templateContext['meta'] ?? null)
            ? $templateContext['meta']
            : (\is_array($fallbackData['meta'] ?? null) ? $fallbackData['meta'] : []);

        return new ViewPayload(
            surface: $this->surfaceFromRouteContext($routeContext, $word),
            operation: $this->operationFromRouteContext($routeContext, $view),
            format: $format,
            intent: 'surface',
            component: $this->componentFromClass($surface::class),
            locations: $locations,
            data: $templateContext + [
                'fallbackData' => $fallbackData,
                'routeContext' => $routeContext,
                'surfaceClass' => $surface::class,
            ],
            meta: [
                'source' => 'surface_renderable_object',
                'surface_class' => $surface::class,
            ] + $meta,
        );
    }

    /**
     * @param array<string, mixed> $templateContext
     * @param array<string, mixed> $fallbackData
     *
     * @return array<string, mixed>
     */
    private function routeContextFrom(array $templateContext, array $fallbackData): array
    {
        $templateWorkbench = \is_array($templateContext['workbench'] ?? null) ? $templateContext['workbench'] : [];
        $fallbackWorkbench = \is_array($fallbackData['workbench'] ?? null) ? $fallbackData['workbench'] : [];

        if (\is_array($templateWorkbench['routeContext'] ?? null)) {
            return $templateWorkbench['routeContext'];
        }

        if (\is_array($fallbackWorkbench['routeContext'] ?? null)) {
            return $fallbackWorkbench['routeContext'];
        }

        if (\is_array($templateContext['routeContext'] ?? null)) {
            return $templateContext['routeContext'];
        }

        return \is_array($fallbackData['routeContext'] ?? null) ? $fallbackData['routeContext'] : [];
    }

    /**
     * @param array<string, mixed> $templateContext
     * @param array<string, mixed> $fallbackData
     *
     * @return array<string, mixed>
     */
    private function locationsFrom(array $templateContext, array $fallbackData): array
    {
        if (\is_array($templateContext['interface'] ?? null) && \is_array($templateContext['interface']['locations'] ?? null)) {
            return $templateContext['interface']['locations'];
        }

        if (\is_array($fallbackData['interface'] ?? null) && \is_array($fallbackData['interface']['locations'] ?? null)) {
            return $fallbackData['interface']['locations'];
        }

        if (\is_array($templateContext['locations'] ?? null)) {
            return $templateContext['locations'];
        }

        return \is_array($fallbackData['locations'] ?? null) ? $fallbackData['locations'] : [];
    }

    /**
     * @param array<string, mixed> $routeContext
     */
    private function surfaceFromRouteContext(array $routeContext, string $fallback): string
    {
        foreach (['surfacePath', 'resourcePath', 'resource'] as $key) {
            if (\is_scalar($routeContext[$key] ?? null)) {
                $value = trim((string) $routeContext[$key]);
                if ('' !== $value) {
                    return $value;
                }
            }
        }

        return $fallback;
    }

    /**
     * @param array<string, mixed> $routeContext
     */
    private function operationFromRouteContext(array $routeContext, string $fallback): string
    {
        if (\is_scalar($routeContext['operation'] ?? null)) {
            $value = trim((string) $routeContext['operation']);
            if ('' !== $value) {
                return $value;
            }
        }

        return $fallback;
    }

    private function stringFrom(mixed $value, string $fallback): string
    {
        if (!\is_scalar($value)) {
            return $fallback;
        }

        $value = trim((string) $value);

        return '' !== $value ? $value : $fallback;
    }

    private function surfaceFromClass(string $class): string
    {
        $shortName = substr(strrchr('\\'.$class, '\\'), 1) ?: 'index';
        $shortName = preg_replace('/SurfaceContract$/', '', $shortName) ?? $shortName;
        $shortName = preg_replace('/[^A-Za-z0-9]+/', '-', $shortName) ?? $shortName;
        $shortName = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $shortName));
        $shortName = trim($shortName, '-');

        return '' !== $shortName ? $shortName : 'index';
    }

    private function componentFromClass(string $class): ?string
    {
        if (1 !== preg_match('/^App\\\\([^\\\\]+)\\\\/', $class, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
