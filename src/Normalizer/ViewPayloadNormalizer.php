<?php

declare(strict_types=1);

namespace App\Viewing\Normalizer;

use App\Viewing\Exception\ViewPayloadException;
use App\Viewing\ServiceInterface\ViewObjectPayloadNormalizerInterface;
use App\Viewing\ServiceInterface\ViewPayloadNormalizerInterface;
use App\Viewing\Value\ViewPayload;

final class ViewPayloadNormalizer implements ViewPayloadNormalizerInterface
{
    private readonly ViewObjectPayloadNormalizerInterface $objectPayloadNormalizer;

    public function __construct(?ViewObjectPayloadNormalizerInterface $objectPayloadNormalizer = null)
    {
        $this->objectPayloadNormalizer = $objectPayloadNormalizer ?? new ViewObjectPayloadNormalizer();
    }

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
}
