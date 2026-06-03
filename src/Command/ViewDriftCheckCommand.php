<?php

declare(strict_types=1);

namespace App\Viewing\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'viewing:drift:check',
    description: 'Checks controlled Symfony controllers for direct rendering/template lookup drift.'
)]
final class ViewDriftCheckCommand extends Command
{
    /**
     * @var array<string, string>
     */
    private const FORBIDDEN_PATTERNS = [
        'controller_render_call' => '/->render\s*\(/',
        'controller_render_view_call' => '/renderView\s*\(/',
        'twig_environment_dependency' => '/Twig\\\\Environment|Environment\s+\$twig/',
        'direct_twig_template_path' => '/[\"\'][^\"\']+\.html\.twig[\"\']/',
        'custom_template_provider' => '/TemplateProvider|TemplateResolver|TemplateAdapter|RenderProvider|RenderAdapter/',
    ];

    protected function configure(): void
    {
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'Directory to scan. Use a producer controller directory for normal checks.',
            'src/Controller'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = (string) $input->getArgument('path');

        if (!is_dir($path)) {
            $output->writeln(sprintf('<comment>Viewing drift check skipped: path does not exist: %s</comment>', $path));

            return Command::SUCCESS;
        }

        $violations = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RegexIterator::GET_MATCH);

        foreach ($files as $match) {
            $file = $match[0] ?? null;

            if (!\is_string($file) || !is_file($file)) {
                continue;
            }

            $content = (string) file_get_contents($file);

            foreach (self::FORBIDDEN_PATTERNS as $rule => $pattern) {
                if (1 === preg_match($pattern, $content)) {
                    $violations[] = [$file, $rule];
                }
            }
        }

        if ([] === $violations) {
            $output->writeln('<info>Viewing drift check passed.</info>');

            return Command::SUCCESS;
        }

        $output->writeln('<error>Viewing drift check failed.</error>');

        foreach ($violations as [$file, $rule]) {
            $output->writeln(sprintf(' - %s: %s', $file, $rule));
        }

        return Command::FAILURE;
    }
}
