<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewResponseGuardService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ViewResponseGuardServiceTest extends TestCase
{
    public function testOffModeDoesNotReportViolation(): void
    {
        $service = new ViewResponseGuardService(guardMode: 'off');

        self::assertFalse($service->isViolation($this->controlledRequest(), $this->htmlResponse()));
    }

    public function testObserveModePreservesResponseAndMarksIt(): void
    {
        $service = new ViewResponseGuardService(guardMode: 'observe');
        $response = $this->htmlResponse();

        self::assertTrue($service->isViolation($this->controlledRequest(), $response));
        self::assertSame($response, $service->observe($response));
        self::assertSame('observed-illegal-controller-render', $response->headers->get('X-Viewing-Guard'));
    }

    public function testEnforceModeReplacesIllegalHtml(): void
    {
        $service = new ViewResponseGuardService(guardMode: 'enforce');
        $request = $this->controlledRequest();
        $response = $this->htmlResponse();

        self::assertTrue($service->isViolation($request, $response));
        self::assertSame(500, $service->replacement($request, $response)->getStatusCode());
    }

    private function controlledRequest(): Request
    {
        $request = Request::create('/vendor');
        $request->attributes->set('_view_controlled', true);

        return $request;
    }

    private function htmlResponse(): Response
    {
        return new Response('<!DOCTYPE html><html></html>', 200, ['Content-Type' => 'text/html']);
    }
}
