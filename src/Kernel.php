<?php

declare(strict_types=1);

namespace App\Viewing;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Bootstraps the standalone Symfony runtime used to exercise the reusable Viewing bundle.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
