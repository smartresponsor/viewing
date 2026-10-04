<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Resolver\ViewTemplateResolver;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class ViewTemplateResolverTest extends TestCase
{
    public function testResolutionSelectsFirstExistingTemplateAndKeepsTrace(): void
    {
        $twig = new Environment(new ArrayLoader([
            'second.html.twig' => 'Second',
            'third.html.twig' => 'Third',
        ]));
        $resolver = new ViewTemplateResolver($twig);

        $resolution = $resolver->resolve(['first.html.twig', 'second.html.twig', 'third.html.twig']);

        self::assertSame('second.html.twig', $resolution->selectedTemplate);
        self::assertSame(['second.html.twig', 'third.html.twig'], $resolution->availableCandidates);
        self::assertSame(['first.html.twig'], $resolution->missingCandidates);
        self::assertCount(3, $resolution->checkedCandidates);
    }

    public function testResolutionIgnoresNonStringCandidateDefensively(): void
    {
        $resolver = new ViewTemplateResolver(new Environment(new ArrayLoader()));

        /** @var list<string> $candidates */
        $candidates = [123, 'missing.html.twig'];
        $resolution = $resolver->resolve($candidates);

        self::assertSame(['missing.html.twig'], $resolution->missingCandidates);
        self::assertCount(1, $resolution->checkedCandidates);
    }

    public function testResolutionSkipsBlankCandidatesAndCapturesLoaderFailure(): void
    {
        $loader = new class implements \Twig\Loader\LoaderInterface {
            public function getSourceContext(string $name): \Twig\Source
            {
                throw new \RuntimeException('loader unavailable');
            }

            public function getCacheKey(string $name): string
            {
                return $name;
            }

            public function isFresh(string $name, int $time): bool
            {
                return false;
            }

            public function exists(string $name): bool
            {
                throw new \RuntimeException('loader unavailable');
            }
        };
        $resolver = new ViewTemplateResolver(new Environment($loader));

        $resolution = $resolver->resolve(['', '  ', 'broken.html.twig', 'broken.html.twig']);

        self::assertNull($resolution->selectedTemplate);
        self::assertCount(1, $resolution->checkedCandidates);
        self::assertSame('broken.html.twig', $resolution->loaderFailures[0]['template'] ?? null);
        self::assertSame(\RuntimeException::class, $resolution->loaderFailures[0]['exception'] ?? null);
    }
}
