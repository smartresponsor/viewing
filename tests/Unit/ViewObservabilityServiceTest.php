<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\ViewObservabilityService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ViewObservabilityServiceTest extends TestCase
{
    public function testStructuredMetricEventIsForwardedToPsrLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('log')
            ->with(
                'warning',
                'viewing.guard_violation',
                self::callback(static fn (array $context): bool => 'viewing' === ($context['component'] ?? null)
                    && 'guard_violation' === ($context['event'] ?? null)
                    && 'viewing_guard_violation_total' === ($context['metric'] ?? null)
                    && 1 === ($context['value'] ?? null)
                    && '/vendor' === ($context['path'] ?? null)),
            );

        (new ViewObservabilityService($logger))->record(
            'guard_violation',
            ['path' => '/vendor'],
            'warning',
        );
    }
}
