<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

/**
 * Defines the public ViewJsonSerializer contract used across the Viewing presentation boundary.
 */
interface ViewJsonSerializerInterface
{
    /**
     * Serializes normalized fallback data into a complete JSON payload without partial output.
     *
     * @param array<string, mixed> $data
     */
    public function serialize(array $data): string;
}
