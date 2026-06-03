<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Service\View\ViewTemplateResolver;
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
}
