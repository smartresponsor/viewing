<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\ViewTrafficClassifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ViewTrafficClassifierTest extends TestCase
{
    public function testBotUserAgentClassifiesAsBot(): void
    {
        $classifier = new ViewTrafficClassifier(['/bot/i']);
        $request = Request::create('/vendor/1', 'GET', server: ['HTTP_USER_AGENT' => 'ExampleBot/1.0']);

        self::assertSame('bot', $classifier->classify($request));
    }

    public function testBrowserFetchHeadersClassifyAsHuman(): void
    {
        $classifier = new ViewTrafficClassifier(['/bot/i']);
        $request = Request::create('/vendor/1', 'GET', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
            'HTTP_SEC_FETCH_MODE' => 'navigate',
        ]);

        self::assertSame('human', $classifier->classify($request));
    }

    public function testMissingUserAgentIsUnknown(): void
    {
        $classifier = new ViewTrafficClassifier(['/bot/i']);
        $request = Request::create('/vendor/1', server: ['HTTP_USER_AGENT' => '']);

        self::assertSame('unknown', $classifier->classify($request));
    }

    public function testNonBrowserHeadersRemainUnknown(): void
    {
        $classifier = new ViewTrafficClassifier(['/bot/i']);
        $request = Request::create('/vendor/1', server: ['HTTP_USER_AGENT' => 'ExampleClient/1.0']);

        self::assertSame('unknown', $classifier->classify($request));
    }

    public function testIncompleteFetchHeadersRemainUnknown(): void
    {
        $classifier = new ViewTrafficClassifier(['/bot/i']);
        $request = Request::create('/vendor/1', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
        ]);

        self::assertSame('unknown', $classifier->classify($request));

        $invalidSite = Request::create('/vendor/1', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_SEC_FETCH_SITE' => 'invalid',
            'HTTP_SEC_FETCH_MODE' => 'navigate',
        ]);
        self::assertSame('unknown', $classifier->classify($invalidSite));

        $invalidMode = Request::create('/vendor/1', server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0',
            'HTTP_SEC_FETCH_SITE' => 'same-origin',
            'HTTP_SEC_FETCH_MODE' => 'invalid',
        ]);
        self::assertSame('unknown', $classifier->classify($invalidMode));
    }

    public function testNonStringPatternIsIgnoredDefensively(): void
    {
        /** @var list<string> $patterns */
        $patterns = [123, '/bot/i'];

        $classifier = new ViewTrafficClassifier($patterns);
        $request = Request::create('/vendor/1', server: ['HTTP_USER_AGENT' => 'ExampleClient/1.0']);

        self::assertSame('unknown', $classifier->classify($request));
    }

    public function testLaterBotPatternCanMatchAfterEarlierPatternMisses(): void
    {
        $classifier = new ViewTrafficClassifier(['/crawler/i', '/bot/i']);
        $request = Request::create('/vendor/1', server: ['HTTP_USER_AGENT' => 'ExampleBot/1.0']);

        self::assertSame('bot', $classifier->classify($request));
    }

    public function testInvalidPatternFailsFast(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ViewTrafficClassifier(['/[invalid/']);
    }
}
