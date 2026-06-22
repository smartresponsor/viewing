<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewObjectPayloadNormalizerInterface;
use App\Viewing\Value\View\ViewPayload;

final class ViewObjectPayloadNormalizer implements ViewObjectPayloadNormalizerInterface
{
    public function supports(mixed $value): bool
    {
        return \is_object($value)
            && method_exists($value, 'toTemplateContext')
            && method_exists($value, 'toFallbackData');
    }

    public function normalize(object $viewObject): ViewPayload
    {
        /** @var array<string, mixed> $templateContext */
        $templateContext = $viewObject->toTemplateContext();

        /** @var array<string, mixed> $fallbackData */
        $fallbackData = $viewObject->toFallbackData();

        $routeContext = $this->routeContextFrom($templateContext, $fallbackData);
        $word = $this->stringFrom($templateContext['word'] ?? $fallbackData['word'] ?? null, $this->viewFromClass($viewObject::class));
        $view = $this->stringFrom($templateContext['view'] ?? $fallbackData['view'] ?? null, 'index');
        $format = $this->stringFrom($templateContext['format'] ?? $fallbackData['format'] ?? null, 'auto');
        $locations = $this->locationsFrom($templateContext, $fallbackData);
        $meta = \is_array($templateContext['meta'] ?? null)
            ? $templateContext['meta']
            : (\is_array($fallbackData['meta'] ?? null) ? $fallbackData['meta'] : []);

        return new ViewPayload(
            surface: $this->viewFromRouteContext($routeContext, $word),
            operation: $this->operationFromRouteContext($routeContext, $view),
            format: $format,
            intent: 'object',
            component: $this->componentFromClass($viewObject::class),
            locations: $locations,
            data: $templateContext + [
                'fallbackData' => $fallbackData,
                'routeContext' => $routeContext,
                'objectClass' => $viewObject::class,
            ],
            meta: [
                'source' => 'view_object_payload',
                'object_class' => $viewObject::class,
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
    private function viewFromRouteContext(array $routeContext, string $fallback): string
    {
        foreach (['viewPath', 'resourcePath', 'resource', 'surfacePath'] as $key) {
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

    private function viewFromClass(string $class): string
    {
        $shortName = substr(strrchr('\\'.$class, '\\'), 1) ?: 'index';
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
