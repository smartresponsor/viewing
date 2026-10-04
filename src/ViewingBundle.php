<?php

declare(strict_types=1);

namespace App\Viewing;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Registers the Viewing Symfony bundle as the reusable Viewing component entrypoint.
 */
final class ViewingBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
