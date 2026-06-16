<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewTemplateRenderer;
use App\Viewing\ServiceInterface\View\ViewTemplateResolverInterface;
use App\Viewing\Value\View\ViewDecision;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;
use App\Viewing\Value\View\ViewTemplateResolution;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class ViewTemplateRendererTest extends TestCase
{
    public function testTemplateThrowableFallsBackToJsonPath(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willThrowException(new \RuntimeException('twig boom'));

        $resolver = $this->createMock(ViewTemplateResolverInterface::class);
        $resolver->method('resolve')->willReturn(new ViewTemplateResolution(
            selectedTemplate: 'vendor/index.html.twig',
            checkedCandidates: [['template' => 'vendor/index.html.twig', 'exists' => true]],
            availableCandidates: ['vendor/index.html.twig'],
            missingCandidates: [],
        ));

        $renderer = new ViewTemplateRenderer($twig, $resolver, new RequestStack());
        $payload = new ViewPayload(surface: 'vendor', operation: 'index');
        $context = new ViewRequestContext('/vendor/index', 'GET');
        $decision = new ViewDecision(ViewDecision::MODE_HTML);

        self::assertNull($renderer->render($payload, $context, $decision));
    }

    public function testStatusCodeDefaultsToOkWhenPayloadHasNoExplicitCode(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('<html></html>');

        $resolver = $this->createMock(ViewTemplateResolverInterface::class);
        $resolver->method('resolve')->willReturn(new ViewTemplateResolution(
            selectedTemplate: 'vendor/index.html.twig',
            checkedCandidates: [['template' => 'vendor/index.html.twig', 'exists' => true]],
            availableCandidates: ['vendor/index.html.twig'],
            missingCandidates: [],
        ));

        $renderer = new ViewTemplateRenderer($twig, $resolver, new RequestStack());
        $payload = new ViewPayload(surface: 'vendor', operation: 'index');
        $context = new ViewRequestContext('/vendor/index', 'GET');
        $decision = new ViewDecision(ViewDecision::MODE_HTML);

        $response = $renderer->render($payload, $context, $decision);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
