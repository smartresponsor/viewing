<?php

declare(strict_types=1);

namespace App\Viewing\Normalizer;

use App\Viewing\Exception\ViewPayloadException;
use App\Viewing\ServiceInterface\ViewObjectPayloadNormalizerInterface;
use App\Viewing\ServiceInterface\ViewPayloadNormalizerInterface;
use App\Viewing\Value\ViewPayload;

/**
 * Normalizes Payload input into the canonical data shape consumed by Viewing.
 */
final class ViewPayloadNormalizer implements ViewPayloadNormalizerInterface
{
    private readonly ViewObjectPayloadNormalizerInterface $objectPayloadNormalizer;

    public function __construct(?ViewObjectPayloadNormalizerInterface $objectPayloadNormalizer = null)
    {
        $this->objectPayloadNormalizer = $objectPayloadNormalizer ?? new ViewObjectPayloadNormalizer();
    }

    /**
     * Reports whether this normalizer can safely handle the supplied Viewing payload representation.
     */
    public function supports(mixed $controllerResult): bool
    {
        if ($controllerResult instanceof ViewPayload) {
            return true;
        }

        if ($this->objectPayloadNormalizer->supports($controllerResult)) {
            return true;
        }

        if (!\is_array($controllerResult)) {
            return false;
        }

        return isset($controllerResult['_view']) || isset($controllerResult['_surface']);
    }

    /**
     * Normalizes the supported input into the stable neutral payload consumed by the Viewing pipeline.
     */
    public function normalize(mixed $controllerResult): ViewPayload
    {
        if ($controllerResult instanceof ViewPayload) {
            return $controllerResult;
        }

        if ($this->objectPayloadNormalizer->supports($controllerResult)) {
            return $this->objectPayloadNormalizer->normalize($controllerResult);
        }

        if (!\is_array($controllerResult)) {
            throw new ViewPayloadException('Unsupported controller result for View payload normalization.');
        }

        return $this->normalizeArrayPayload($controllerResult);
    }

    /**
     * @param array<string, mixed> $controllerResult
     */
    private function normalizeArrayPayload(array $controllerResult): ViewPayload
    {
        $view = \is_array($controllerResult['_view'] ?? null) ? $controllerResult['_view'] : [];

        $surface = $this->requiredString($view['surface'] ?? $controllerResult['_surface'] ?? null, '_view.surface');
        $operation = $this->requiredString($view['operation'] ?? $controllerResult['_operation'] ?? null, '_view.operation');

        return new ViewPayload(
            surface: $surface,
            operation: $operation,
            format: $this->optionalString($view['format'] ?? $controllerResult['_format'] ?? null) ?? 'auto',
            intent: $this->optionalString($view['intent'] ?? $controllerResult['_intent'] ?? null),
            component: $this->optionalString($view['component'] ?? $controllerResult['_component'] ?? null),
            locations: $this->interfaceLocationsFromControllerResult($controllerResult),
            data: \is_array($controllerResult['data'] ?? null) ? $controllerResult['data'] : [],
            meta: \is_array($controllerResult['meta'] ?? null) ? $controllerResult['meta'] : [],
            debug: \is_array($controllerResult['debug'] ?? null) ? $controllerResult['debug'] : [],
        );
    }

    private function requiredString(mixed $value, string $field): string
    {
        $value = $this->optionalString($value);
        if (null === $value) {
            throw ViewPayloadException::missingRequiredField($field);
        }

        return $value;
    }

    private function optionalString(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
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
}
