<?php

declare(strict_types=1);

namespace App\Viewing\Exception;

final class ViewPayloadException extends \InvalidArgumentException
{
    public static function missingRequiredField(string $field): self
    {
        return new self(sprintf('View payload is missing required field "%s".', $field));
    }
}
