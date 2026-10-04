<?php

declare(strict_types=1);

namespace App\Viewing\ValueObjectInterface;

/**
 * Defines the public ViewObjectPayload contract used across the Viewing presentation boundary.
 */
interface ViewObjectPayloadInterface
{
    /**
     * Returns producer data that may be exposed to the selected Interfacing template.
     *
     * @return array<string, mixed>
     */
    public function toTemplateContext(): array;

    /**
     * Returns producer data that may be serialized by the structured JSON fallback path.
     *
     * @return array<string, mixed>
     */
    public function toFallbackData(): array;
}
