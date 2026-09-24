<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Resolver\ViewStatusCodeResolver;
use App\Viewing\Value\ViewPayload;
use PHPUnit\Framework\TestCase;

final class ViewStatusCodeResolverTest extends TestCase
{
    public function testPayloadStatusCodeIsResolved(): void
    {
        $resolver = new ViewStatusCodeResolver();
        $payload = new ViewPayload('vendor', 'show', meta: ['status_code' => '422']);

        self::assertSame(422, $resolver->resolve($payload));
    }

    public function testOverrideWins(): void
    {
        $resolver = new ViewStatusCodeResolver();
        $payload = new ViewPayload('vendor', 'show', meta: ['status_code' => 404]);

        self::assertSame(500, $resolver->resolve($payload, 500));
    }

    public function testInvalidStatusFallsBack(): void
    {
        $resolver = new ViewStatusCodeResolver();
        $payload = new ViewPayload('vendor', 'show', meta: ['status_code' => 900]);

        self::assertSame(202, $resolver->resolve($payload, fallback: 202));
    }
}
