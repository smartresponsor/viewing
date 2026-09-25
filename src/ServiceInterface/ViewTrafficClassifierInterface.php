<?php

declare(strict_types=1);

namespace App\Viewing\ServiceInterface;

use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the public ViewTrafficClassifier contract used across the Viewing presentation boundary.
 */
interface ViewTrafficClassifierInterface
{
    /**
     * Classifies request traffic as human, bot, or unknown for representation policy decisions.
     */
    public function classify(Request $request): string;
}
