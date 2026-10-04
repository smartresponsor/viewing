<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Viewing\Service\ViewTemplateCandidateService;
use App\Viewing\Value\ViewPayload;
use App\Viewing\Value\ViewRequestContext;

$context = new ViewRequestContext('/probe', 'GET');
$cases = [
    'valid' => [new ViewTemplateCandidateService(), new ViewPayload('Vendor Profile', 'show', component: 'Cruding')],
    'null-component' => [new ViewTemplateCandidateService(), new ViewPayload('Vendor', 'index')],
    'blank-component' => [new ViewTemplateCandidateService(), new ViewPayload('Vendor', 'index', component: '   ')],
    'invalid-component' => [new ViewTemplateCandidateService(), new ViewPayload('Vendor', 'index', component: '---')],
    'disabled-local' => [new ViewTemplateCandidateService(localComponentFallbackEnabled: false), new ViewPayload('Vendor', 'show', component: 'Cruding')],
];

$file = realpath(dirname(__DIR__).'/src/Service/ViewTemplateCandidateService.php');
foreach ($cases as $label => [$service, $payload]) {
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
    $service->candidates($payload, $context);
    $entry = xdebug_get_code_coverage()[$file] ?? [];
    xdebug_stop_code_coverage(true);

    echo "CASE: ".$label.PHP_EOL;
    $data = $entry['functions']['App\\Viewing\\Service\\ViewTemplateCandidateService->localComponentCandidates'] ?? [];
    foreach (($data['paths'] ?? []) as $index => $path) {
        if (($path['hit'] ?? 0) > 0) {
            echo sprintf("  path %d: %s", $index, json_encode($path['path'] ?? [])).PHP_EOL;
        }
    }
}
