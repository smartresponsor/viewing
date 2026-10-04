<?php

declare(strict_types=1);

namespace App\Viewing\Exception;

/**
 * Represents the ViewPayload failure contract exposed by the Viewing payload pipeline.
 */
final class ViewPayloadException extends \InvalidArgumentException
{
    /**
     * Creates a payload exception that identifies a required neutral view field that is absent.
     */
    public static function missingRequiredField(string $field): self
    {
        return new self(sprintf('View payload is missing required field "%s".', $field));
    }
}
