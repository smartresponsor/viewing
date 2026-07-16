<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Kernel;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[RunTestsInSeparateProcesses]
final class ViewKernelPipelineTest extends TestCase
{
    public function testBotRequestTraversesKernelPipelineAndReturnsJson(): void
    {
        $kernel = new Kernel('test', true);

        try {
            $response = $kernel->handle(Request::create('/viewing', 'GET', server: [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'ExampleBot/1.0',
            ]));

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));
            self::assertStringContainsString('actor_type_forces_json', (string) $response->getContent());
        } finally {
            $kernel->shutdown();
        }
    }

    public function testHumanRequestTraversesKernelPipelineAndReturnsHtml(): void
    {
        $kernel = new Kernel('test', true);

        try {
            $response = $kernel->handle(Request::create('/viewing', 'GET', server: [
                'HTTP_ACCEPT' => 'text/html',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'HTTP_SEC_FETCH_SITE' => 'same-origin',
                'HTTP_SEC_FETCH_MODE' => 'navigate',
            ]));

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertStringStartsWith('text/html', (string) $response->headers->get('Content-Type'));
            self::assertSame('1', $response->headers->get('X-Viewing-Rendered'));
        } finally {
            $kernel->shutdown();
        }
    }
}
