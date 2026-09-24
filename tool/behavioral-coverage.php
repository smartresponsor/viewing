<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$sources = [
    'kernel' => (string) file_get_contents($root.'/tests/Unit/ViewKernelPipelineTest.php'),
    'browser' => (string) file_get_contents($root.'/tests/Browser/viewing.spec.mjs'),
];

$inventories = [
    'functional' => [
        'route:viewing:human-html' => str_contains($sources['kernel'], 'testRealStandaloneKernelStillProvesHumanHtmlPath')
            && str_contains($sources['browser'], 'standalone Viewing home renders through the central view boundary'),
        'route:viewing:bot-json' => str_contains($sources['kernel'], 'testRealStandaloneKernelStillProvesBotJsonPath'),
    ],
    'behavioral' => [
        'decision:human-html' => str_contains($sources['kernel'], 'testRealStandaloneKernelStillProvesHumanHtmlPath'),
        'decision:bot-json' => str_contains($sources['kernel'], 'testRealStandaloneKernelStillProvesBotJsonPath'),
        'fallback:missing-template-json' => str_contains($sources['kernel'], 'testMissingCandidateFallsBackToJsonWithOriginalStatus'),
        'guard:observe' => str_contains($sources['kernel'], 'testGuardObserveAndEnforceModesAreAppliedInResponseEvent'),
        'guard:enforce' => str_contains($sources['kernel'], 'testGuardObserveAndEnforceModesAreAppliedInResponseEvent'),
    ],
    'ui' => [
        'ui:viewing-home-render' => str_contains($sources['browser'], 'standalone Viewing home renders through the central view boundary'),
    ],
    'critical' => [
        'critical:central-view-boundary' => str_contains($sources['kernel'], 'testRealStandaloneKernelStillProvesHumanHtmlPath')
            && str_contains($sources['kernel'], 'testRealStandaloneKernelStillProvesBotJsonPath')
            && str_contains($sources['browser'], 'standalone Viewing home renders through the central view boundary'),
    ],
];

$dimensions = [];
foreach ($inventories as $dimension => $inventory) {
    $eligible = array_keys($inventory);
    $covered = [];
    foreach ($inventory as $identifier => $present) {
        if ($present) {
            $covered[] = $identifier;
        }
    }
    $dimensions[$dimension] = [
        'eligible' => $eligible,
        'covered' => $covered,
    ];
}

$evidence = [
    'schema' => 'behavioral-ui-coverage-v2',
    'generatedAt' => (new DateTimeImmutable())->format(DATE_ATOM),
    'producer' => [
        'kind' => 'repository_script',
        'script' => 'test:behavioral-coverage',
    ],
    'dimensions' => $dimensions,
];

$directory = $root.'/var/coverage';
if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
    fwrite(STDERR, "Unable to create var/coverage.\n");
    exit(1);
}

$encoded = json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if (false === $encoded || false === file_put_contents($directory.'/behavioral-ui.json', $encoded.PHP_EOL)) {
    fwrite(STDERR, "Unable to write behavioral/UI coverage evidence.\n");
    exit(1);
}

echo "Behavioral/UI coverage evidence generated.\n";
