<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use App\Interfacing\Contract\InterfaceSurfaceRenderableInterface;
use App\Viewing\Value\ViewPayload;
use App\Viewing\ValueObjectInterface\ViewObjectPayloadInterface;

/**
 * Defines the public ViewObjectPayloadNormalizer contract used across the Viewing presentation boundary.
 */
interface ViewObjectPayloadNormalizerInterface
{
    /**
     * Reports whether this normalizer can safely handle the supplied Viewing payload representation.
     */
    public function supports(mixed $value): bool;

    /**
     * Normalizes the supported input into the stable neutral payload consumed by the Viewing pipeline.
     */
    public function normalize(ViewObjectPayloadInterface|InterfaceSurfaceRenderableInterface $viewObject): ViewPayload;
}
