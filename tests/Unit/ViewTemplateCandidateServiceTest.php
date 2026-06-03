<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewTemplateCandidateService;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;
use PHPUnit\Framework\TestCase;

final class ViewTemplateCandidateServiceTest extends TestCase
{
    public function testCanonicalTemplateHierarchyIsResourceIndexThenInterfacingRootThenLocalIndex(): void
    {
        $service = new ViewTemplateCandidateService();
        $payload = new ViewPayload(surface: 'Vendor Profile', operation: 'Show', intent: 'Profile', component: 'Cruding');
        $context = new ViewRequestContext('/vendor/1', 'GET');

        $candidates = $service->candidates($payload, $context);

        self::assertSame([
            '@Interfacing/vendor-profile/index.html.twig',
            '@Interfacing/index.html.twig',
            '@Cruding/index.html.twig',
            '@Viewing/view/index.html.twig',
        ], $candidates);
    }

    public function testViewingSelfProcessingUsesTheSameHierarchy(): void
    {
        $service = new ViewTemplateCandidateService();
        $payload = new ViewPayload(surface: 'View', operation: 'Index', intent: 'Home', component: 'Viewing');
        $context = new ViewRequestContext('/viewing', 'GET');

        $candidates = $service->candidates($payload, $context);

        self::assertSame([
            '@Interfacing/view/index.html.twig',
            '@Interfacing/index.html.twig',
            '@Viewing/index.html.twig',
            '@Viewing/view/index.html.twig',
        ], $candidates);
    }

    public function testCanDisableLocalComponentFallback(): void
    {
        $service = new ViewTemplateCandidateService(localComponentFallbackEnabled: false);
        $payload = new ViewPayload(surface: 'Vendor', operation: 'Show', intent: 'Profile', component: 'Cruding');
        $context = new ViewRequestContext('/vendor/1', 'GET');

        $candidates = $service->candidates($payload, $context);

        self::assertSame([
            '@Interfacing/vendor/index.html.twig',
            '@Interfacing/index.html.twig',
            '@Viewing/view/index.html.twig',
        ], $candidates);
    }

    public function testCanDisableViewingDiagnosticFallback(): void
    {
        $service = new ViewTemplateCandidateService(diagnosticMode: 'off');
        $payload = new ViewPayload(surface: 'Vendor', operation: 'Show', intent: 'Profile', component: 'Cruding');
        $context = new ViewRequestContext('/vendor/1', 'GET');

        $candidates = $service->candidates($payload, $context);

        self::assertSame([
            '@Interfacing/vendor/index.html.twig',
            '@Interfacing/index.html.twig',
            '@Cruding/index.html.twig',
        ], $candidates);
    }
}
