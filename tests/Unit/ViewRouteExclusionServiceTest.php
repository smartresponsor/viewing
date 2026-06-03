<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewRouteExclusionService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ViewRouteExclusionServiceTest extends TestCase
{
    public function testExcludesProfilerPath(): void
    {
        $service = new ViewRouteExclusionService(['#^/_profiler#'], []);

        self::assertTrue($service->isExcluded(Request::create('/_profiler/latest')));
    }

    public function testExcludesConfiguredRoute(): void
    {
        $service = new ViewRouteExclusionService([], ['#^_wdt#']);
        $request = Request::create('/_wdt/token');
        $request->attributes->set('_route', '_wdt');

        self::assertTrue($service->isExcluded($request));
    }

    public function testAllowsNormalRoute(): void
    {
        $service = new ViewRouteExclusionService(['#^/_profiler#'], ['#^_wdt#']);
        $request = Request::create('/vendor/1');
        $request->attributes->set('_route', 'vendor_show');

        self::assertFalse($service->isExcluded($request));
    }
}
