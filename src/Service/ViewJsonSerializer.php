<?php

declare(strict_types=1);

namespace App\Viewing\Service;

use App\Viewing\ServiceInterface\ViewJsonSerializerInterface;

/**
 * Serializes normalized Viewing fallback payloads with the component's strict JSON encoding contract.
 */
final readonly class ViewJsonSerializer implements ViewJsonSerializerInterface
{
    /**
     * Serializes normalized fallback data into a complete JSON payload without partial output.
     */
    public function serialize(array $data): string
    {
        return json_encode(
            $data,
            \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_THROW_ON_ERROR,
            64,
        );
    }
}
