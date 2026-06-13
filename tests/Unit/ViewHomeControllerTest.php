<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Controller\View\ViewHomeController;
use PHPUnit\Framework\TestCase;

final class ViewHomeControllerTest extends TestCase
{
    public function testHomeControllerReturnsViewPayloadOnly(): void
    {
        $payload = (new ViewHomeController())();

        self::assertSame('view', $payload['_view']['surface']);
        self::assertSame('index', $payload['_view']['operation']);
        self::assertSame('Viewing', $payload['_view']['component']);
        self::assertArrayHasKey('interface', $payload);
        self::assertArrayHasKey('locations', $payload['interface']);
        self::assertArrayHasKey('data', $payload);
        self::assertArrayHasKey('meta', $payload);
    }
}
