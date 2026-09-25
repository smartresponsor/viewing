<?php

declare(strict_types=1);

namespace App\Viewing\Test\Unit;

use App\Viewing\Command\ViewDriftCheckCommand;
use App\Viewing\DependencyInjection\Configuration;
use App\Viewing\DependencyInjection\ViewingExtension;
use App\Viewing\ViewingBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\DependencyInjection\TwigExtension;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ViewInfrastructureSurfaceTest extends TestCase
{
    public function testDriftCommandHandlesMissingCleanAndViolatingPaths(): void
    {
        $tester = new CommandTester(new ViewDriftCheckCommand());

        self::assertSame(Command::SUCCESS, $tester->execute(['path' => __DIR__.'/missing-viewing-path']));
        self::assertStringContainsString('path does not exist', $tester->getDisplay());

        $directory = sys_get_temp_dir().'/viewing-drift-'.bin2hex(random_bytes(4));
        self::assertTrue(mkdir($directory));

        try {
            file_put_contents($directory.'/CleanController.php', "<?php\nfinal class CleanController {}\n");
            self::assertSame(Command::SUCCESS, $tester->execute(['path' => $directory]));
            self::assertStringContainsString('passed', $tester->getDisplay());

            file_put_contents($directory.'/DriftController.php', "<?php\nfinal class DriftController { public function run() { return \$this->render('bad.html.twig'); } }\n");
            self::assertSame(Command::FAILURE, $tester->execute(['path' => $directory]));
            self::assertStringContainsString('controller_render_call', $tester->getDisplay());
            self::assertStringContainsString('direct_twig_template_path', $tester->getDisplay());
        } finally {
            @unlink($directory.'/CleanController.php');
            @unlink($directory.'/DriftController.php');
            @rmdir($directory);
        }
    }

    public function testConfigurationBuildsViewingTree(): void
    {
        $tree = (new Configuration())->getConfigTreeBuilder()->buildTree();

        self::assertSame('viewing', $tree->getName());
    }

    public function testExtensionPrependsTwigPathsOnlyWhenTwigExtensionExists(): void
    {
        $extension = new ViewingExtension();

        $withoutTwig = new ContainerBuilder();
        $extension->prepend($withoutTwig);
        self::assertSame([], $withoutTwig->getExtensionConfig('twig'));

        $withTwig = new ContainerBuilder();
        $withTwig->registerExtension(new TwigExtension());
        $extension->prepend($withTwig);

        $twigConfig = $withTwig->getExtensionConfig('twig');
        self::assertNotEmpty($twigConfig);
        self::assertSame(
            'Viewing',
            array_values($twigConfig[0]['paths'])[0] ?? null,
        );
    }

    public function testExtensionLoadsDefaultParametersAndServices(): void
    {
        $container = new ContainerBuilder();

        (new ViewingExtension())->load([], $container);

        self::assertTrue($container->getParameter('viewing.enabled'));
        self::assertSame('html', $container->getParameter('viewing.unknown_actor_policy'));
        self::assertSame('Viewing', $container->getParameter('viewing.viewing_twig_namespace'));
        self::assertTrue($container->hasDefinition('App\\Viewing\\Service\\ViewDecisionService'));
    }

    public function testBundlePathPointsAtRepositoryRoot(): void
    {
        self::assertSame(dirname(__DIR__, 2), (new ViewingBundle())->getPath());
    }
}
