<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewTrafficClassifier;
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
        $request = Request::create('/vendor/1');

        self::assertSame('unknown', $classifier->classify($request));
    }
}
