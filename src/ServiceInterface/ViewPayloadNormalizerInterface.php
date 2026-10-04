<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Viewing\Value\ViewPayload;

/**
 * Defines the public ViewPayloadNormalizer contract used across the Viewing presentation boundary.
 */
interface ViewPayloadNormalizerInterface
{
    /**
     * Reports whether this normalizer can safely handle the supplied Viewing payload representation.
     */
    public function supports(mixed $controllerResult): bool;

    /**
     * Normalizes the supported input into the stable neutral payload consumed by the Viewing pipeline.
     */
    public function normalize(mixed $controllerResult): ViewPayload;
}
