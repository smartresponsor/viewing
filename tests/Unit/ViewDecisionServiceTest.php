<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewDecisionService;
use App\Viewing\Value\View\ViewDecision;
use App\Viewing\Value\View\ViewPayload;
use App\Viewing\Value\View\ViewRequestContext;
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
}
