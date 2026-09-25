<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Viewing\Service\ViewRouteExclusionService;
use Symfony\Component\HttpFoundation\Request;

$cases = [
    'empty' => [[], [], '/vendor', null],
    'blank-only' => [[''], [], '/vendor', null],
    'invalid-only' => [['/[/' ], [], '/vendor', null],
    'single-miss' => [['#^/_profiler#'], [], '/vendor', null],
    'single-hit' => [['#^/_profiler#'], [], '/_profiler/latest', null],
    'miss-then-hit' => [['#^/nope#', '#^/vendor#'], [], '/vendor', null],
    'hit-then-extra' => [['#^/vendor#', '#^/nope#'], [], '/vendor', null],
];

$file = realpath(dirname(__DIR__).'/src/Service/ViewRouteExclusionService.php');
foreach ($cases as $label => [$paths, $routes, $path, $route]) {
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK);
    $service = new ViewRouteExclusionService($paths, $routes);
    $request = Request::create($path);
    if (null !== $route) {
        $request->attributes->set('_route', $route);
    }
    $service->isExcluded($request);
    $entry = xdebug_get_code_coverage()[$file] ?? [];
    xdebug_stop_code_coverage(true);

    echo "CASE: ".$label.PHP_EOL;
    $data = $entry['functions']['App\\Viewing\\Service\\ViewRouteExclusionService->matchesAny'] ?? [];
    foreach (($data['paths'] ?? []) as $index => $pathData) {
        if (($pathData['hit'] ?? 0) > 0) {
            echo sprintf("  path %d: %s", $index, json_encode($pathData['path'] ?? [])).PHP_EOL;
        }
    }
}
