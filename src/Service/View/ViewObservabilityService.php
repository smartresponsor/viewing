<?php

declare(strict_types=1);

namespace App\Viewing\Service\View;

use App\Viewing\ServiceInterface\View\ViewObservabilityServiceInterface;
use Psr\Log\LoggerInterface;

final readonly class ViewObservabilityService implements ViewObservabilityServiceInterface
{
    public function __construct(private ?LoggerInterface $logger = null)
    {
    }

    public function record(string $event, array $context = [], string $level = 'info'): void
    {
        if (null === $this->logger) {
            return;
        }

        $payload = [
            'component' => 'viewing',
            'event' => $event,
            'metric' => 'viewing_'.$event.'_total',
            'value' => 1,
        ] + $context;

        $this->logger->log($level, 'viewing.'.$event, $payload);
    }
}
