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
}
