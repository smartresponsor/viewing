<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\ViewDecisionService;
use App\Viewing\Value\ViewDecision;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;
use PHPUnit\Framework\TestCase;

final class ViewDecisionServiceTest extends TestCase
{
    public function testBotActorForcesJson(): void
    {
        $service = new ViewDecisionService(['bot']);

        $decision = $service->decide(
            new ViewPayload('vendor', 'show'),
            new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'html', 'bot', true, false, false),
        );

        self::assertSame(ViewDecision::MODE_JSON, $decision->mode);
        self::assertContains('actor_type_forces_json', $decision->reasons);
    }

    public function testHumanHtmlRequestAllowsHtmlCandidate(): void
    {
        $service = new ViewDecisionService(['bot']);

        $decision = $service->decide(
            new ViewPayload('vendor', 'show'),
            new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'html', 'human', true, false, false),
        );

        self::assertSame(ViewDecision::MODE_HTML, $decision->mode);
    }

    public function testUnknownActorCanBeConfiguredToForceJson(): void
    {
        $service = new ViewDecisionService(['bot'], ViewDecision::MODE_JSON);

        $decision = $service->decide(
            new ViewPayload('vendor', 'show'),
            new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'html', 'unknown', true, false, false),
        );

        self::assertSame(ViewDecision::MODE_JSON, $decision->mode);
        self::assertContains('unknown_actor_forces_json', $decision->reasons);
    }

    public function testInvalidUnknownActorPolicyFailsFast(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ViewDecisionService(['bot'], 'pass');
    }

    public function testDecisionPrecedenceCoversExplicitFormatsControlledRoutesAndRequestPreferences(): void
    {
        $service = new ViewDecisionService([' BOT ', '', 'bot']);

        $requestJson = $service->decide(
            new ViewPayload('vendor', 'show'),
            new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'json', 'human'),
        );
        self::assertSame('request_format_json', $requestJson->reasons[0]);

        $payloadJson = $service->decide(
            new ViewPayload('vendor', 'show', format: 'json'),
            new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'html', 'human'),
        );
        self::assertSame('payload_format_json', $payloadJson->reasons[0]);

        $controlled = $service->decide(
            new ViewPayload('vendor', 'show'),
            new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'html', 'human', false, false, false, ['_view_controlled' => true]),
        );
        self::assertSame('view_controlled_html_route', $controlled->reasons[0]);

        $acceptJson = $service->decide(
            new ViewPayload('vendor', 'show'),
            new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'html', 'human', false, true, false),
        );
        self::assertSame('accept_header_prefers_json', $acceptJson->reasons[0]);

        $xhr = $service->decide(
            new ViewPayload('vendor', 'show'),
            new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'html', 'human', false, false, true),
        );
        self::assertSame('xml_http_request_without_html_preference', $xhr->reasons[0]);

        $bot = $service->decide(
            new ViewPayload('vendor', 'show'),
            new ViewRequestContext('/vendor/1', 'GET', 'vendor_show', 'html', 'BoT'),
        );
        self::assertSame('actor_type_forces_json', $bot->reasons[0]);
    }
}
